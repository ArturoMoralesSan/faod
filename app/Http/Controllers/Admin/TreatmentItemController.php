<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\OdontogramEntry;
use App\Models\Procedure;
use App\Models\ProcedureMaterial;
use App\Models\TreatmentPlan;
use App\Models\TreatmentProcedure;
use App\Models\TreatmentStage;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Etapas, procedimientos y materiales de un plan de tratamiento.
 */
class TreatmentItemController extends Controller
{
    /* ------------------------------- Etapas ------------------------------- */

    public function storeStage(Request $request, TreatmentPlan $treatmentPlan): RedirectResponse
    {
        $treatmentPlan->stages()->create([
            'order' => ($treatmentPlan->stages()->max('order') ?? 0) + 1,
            ...$this->stageRules($request),
        ]);

        return back()->with('success', 'Etapa agregada.');
    }

    public function updateStage(Request $request, TreatmentStage $stage): RedirectResponse
    {
        $stage->update($this->stageRules($request));

        return back()->with('success', 'Etapa actualizada.');
    }

    public function destroyStage(TreatmentStage $stage): RedirectResponse
    {
        // Sus procedimientos se conservan sin etapa.
        $stage->delete();

        return back()->with('success', 'Etapa eliminada.');
    }

    private function stageRules(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'order' => ['sometimes', 'integer', 'min:1'],
        ]);
    }

    /* --------------------------- Procedimientos --------------------------- */

    public function storeProcedure(Request $request, TreatmentPlan $treatmentPlan): RedirectResponse
    {
        $procedure = new TreatmentProcedure([
            ...$this->procedureRules($request, $treatmentPlan),
            'treatment_plan_id' => $treatmentPlan->id,
            'patient_id' => $treatmentPlan->patient_id,
        ]);

        $this->applyDefaults($procedure);
        $procedure->save();

        return back()->with('success', 'Procedimiento agregado al plan.');
    }

    public function updateProcedure(Request $request, TreatmentProcedure $procedure): RedirectResponse
    {
        $procedure->fill($this->procedureRules($request, $procedure->plan));
        $this->applyDefaults($procedure);
        $procedure->save();

        return back()->with('success', 'Procedimiento actualizado.');
    }

    public function destroyProcedure(TreatmentProcedure $procedure): RedirectResponse
    {
        $procedure->delete();

        return back()->with('success', 'Procedimiento eliminado del plan.');
    }

    /**
     * Costo del catálogo si no se indicó y estado de validación docente:
     * solo un procedimiento realizado se valida; cualquier cambio en uno realizado
     * vuelve a requerir validación.
     */
    private function applyDefaults(TreatmentProcedure $procedure): void
    {
        if ($procedure->cost === null && $procedure->procedure_id) {
            $procedure->cost = Procedure::find($procedure->procedure_id)?->default_cost;
        }

        if ($procedure->status === 'realizado') {
            $procedure->performed_at ??= now();

            if (! $procedure->exists || $procedure->isDirty()) {
                $procedure->review_status = 'pendiente';
            }
        } else {
            $procedure->review_status = null;
            $procedure->performed_at = null;
        }
    }

    private function procedureRules(Request $request, TreatmentPlan $plan): array
    {
        $data = $request->validate([
            'treatment_stage_id' => ['nullable', Rule::exists('treatment_stages', 'id')->where('treatment_plan_id', $plan->id)],
            'procedure_id' => ['nullable', 'required_without:description', Rule::exists('procedures', 'id')],
            'description' => ['nullable', 'required_without:procedure_id', 'string', 'max:255'],
            'patient_diagnosis_id' => ['nullable', Rule::exists('patient_diagnoses', 'id')->where('patient_id', $plan->patient_id)],
            'tooth' => ['nullable', Rule::in(OdontogramEntry::teeth())],
            'surfaces' => ['nullable', 'string', 'max:5', 'regex:/^[OMDVL]+$/'],
            'status' => ['required', Rule::in(TreatmentProcedure::STATUSES)],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'performed_at' => ['nullable', 'date', 'before_or_equal:now'],
            'student_profile_id' => ['nullable', Rule::exists('student_profiles', 'id')->whereNull('deleted_at')],
            'teacher_profile_id' => ['nullable', Rule::exists('teacher_profiles', 'id')->whereNull('deleted_at')],
            'notes' => ['nullable', 'string'],
            'patient_instructions' => ['nullable', 'string'],
        ], [
            'procedure_id.required_without' => 'Elige un procedimiento del catálogo o escribe una descripción.',
            'description.required_without' => 'Elige un procedimiento del catálogo o escribe una descripción.',
        ]);

        if (! empty($data['surfaces'])) {
            $data['surfaces'] = implode('', array_values(array_intersect(['M', 'O', 'D', 'V', 'L'], str_split($data['surfaces']))));
        }

        return $data;
    }

    /* ----------------------------- Materiales ----------------------------- */

    /**
     * Material utilizado. Si se elige un artículo del inventario, se descuenta
     * de la existencia del almacén indicado.
     */
    public function storeMaterial(Request $request, TreatmentProcedure $procedure, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validate([
            'inventory_item_id' => ['nullable', Rule::exists('inventory_items', 'id')->whereNull('deleted_at')],
            'warehouse_id' => ['nullable', 'required_with:inventory_item_id', 'exists:warehouses,id'],
            'name' => ['nullable', 'required_without:inventory_item_id', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit' => ['nullable', 'string', 'max:20'],
        ], [
            'warehouse_id.required_with' => 'Indica de qué almacén se toma el material.',
        ]);

        DB::transaction(function () use ($data, $procedure, $inventory) {
            if (! empty($data['inventory_item_id'])) {
                $item = InventoryItem::findOrFail($data['inventory_item_id']);
                $data['name'] = $item->name;
                $data['unit'] = $item->unit;

                $inventory->withdraw($item, Warehouse::findOrFail($data['warehouse_id']), (float) $data['quantity'], [
                    'type' => 'consumo',
                    'reason' => 'Material de procedimiento',
                    'reference' => "Plan #{$procedure->treatment_plan_id} · Proc. #{$procedure->id}",
                    'treatment_procedure_id' => $procedure->id,
                ]);
            }

            $procedure->materials()->create($data);
        });

        return back()->with('success', 'Material registrado.');
    }

    /**
     * Quita el material; si salió del inventario, se devuelve al almacén.
     */
    public function destroyMaterial(ProcedureMaterial $material, InventoryService $inventory): RedirectResponse
    {
        DB::transaction(function () use ($material, $inventory) {
            if ($material->item && $material->warehouse) {
                $inventory->receive($material->item, $material->warehouse, $material->quantity, [
                    'type' => 'entrada',
                    'reason' => 'Devolución de material de procedimiento',
                    'reference' => "Proc. #{$material->treatment_procedure_id}",
                    // Si el artículo exige caducidad, se devuelve con la del lote más próximo.
                    'expires_at' => $material->item->tracks_expiration
                        ? ($material->item->lots()->whereNotNull('expires_at')->orderBy('expires_at')->first()?->expires_at?->toDateString() ?? today()->addYear()->toDateString())
                        : null,
                ]);
            }

            $material->delete();
        });

        return back()->with('success', 'Material eliminado.');
    }
}
