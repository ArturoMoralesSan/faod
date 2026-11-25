<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\OdontogramEntry;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\Review;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\TreatmentPlan;
use App\Models\TreatmentProcedure;
use App\Models\Warehouse;
use App\Support\TeacherScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Planes de tratamiento. Se crean desde el expediente del paciente.
 */
class TreatmentPlanController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $plan = $patient->treatmentPlans()->create([
            ...$this->validated($request),
            'review_status' => 'pendiente',
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.treatment-plans.show', $plan)
            ->with('success', 'Plan de tratamiento creado; queda pendiente de autorización docente.');
    }

    public function show(TreatmentPlan $treatmentPlan): Response
    {
        TeacherScope::ensureVisible($treatmentPlan, request()->user());

        $people = ':id,first_name,middle_name,paternal_surname,maternal_surname';

        $treatmentPlan->load([
            "patient.people{$people}",
            'patient.allergies',
            "student.people{$people}",
            "teacher.people{$people}",
            'creator:id,name',
            'reviews.reviewer:id,name',
            'stages',
            'procedures' => fn ($q) => $q
                ->with([
                    'procedure:id,code,name',
                    'diagnosis.diagnosis:id,code,name',
                    "student.people{$people}",
                    "teacher.people{$people}",
                    'materials',
                    'reviews.reviewer:id,name',
                ])
                ->orderBy('id'),
        ]);

        $user = request()->user();

        return Inertia::render('admin/treatment-plans/Show', [
            'plan' => $treatmentPlan,
            'statuses' => TreatmentPlan::STATUSES,
            'procedureStatuses' => TreatmentProcedure::STATUSES,
            'decisions' => Review::DECISIONS,
            'catalog' => Procedure::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name', 'category', 'default_cost']),
            'diagnoses' => $treatmentPlan->patient->diagnoses()
                ->with('diagnosis:id,code,name')
                ->get(['id', 'diagnosis_id', 'description', 'tooth', 'surfaces', 'review_status']),
            'teeth' => OdontogramEntry::teeth(),
            'inventoryItems' => InventoryItem::where('is_active', true)->withStock()->orderBy('name')->get(['id', 'code', 'name', 'unit']),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'clinic_id']),
            'students' => StudentProfile::query()
                ->whereHas('enrollments', fn ($q) => $q->where('status', 'activo'))
                ->with('people:id,first_name,paternal_surname,maternal_surname')
                ->get(['id', 'people_id', 'registration_number']),
            'teachers' => TeacherProfile::where('status', 'active')
                ->with('people:id,first_name,paternal_surname,maternal_surname')
                ->get(['id', 'people_id']),
            'can' => [
                'edit' => $user->can('treatments.edit'),
                'delete' => $user->can('treatments.delete'),
                'review' => $user->can('reviews.create'),
                'charge' => $user->can('charges.create'),
            ],
            // Procedimientos realizados que aún no se cobran.
            'uncharged' => $treatmentPlan->procedures()
                ->where('status', 'realizado')
                ->where('cost', '>', 0)
                ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('charges')->whereColumn('charges.treatment_procedure_id', 'treatment_procedures.id'))
                ->count(),
        ]);
    }

    public function update(Request $request, TreatmentPlan $treatmentPlan): RedirectResponse
    {
        $treatmentPlan->fill($this->validated($request));

        // Cambiar el contenido del plan requiere autorizarlo otra vez; cambiar solo el estado, no.
        if ($treatmentPlan->isDirty(['title', 'notes', 'teacher_profile_id'])) {
            $treatmentPlan->review_status = 'pendiente';
        }

        if ($treatmentPlan->status === 'concluido' && ! $treatmentPlan->completed_at) {
            $treatmentPlan->completed_at = today();
        }

        $treatmentPlan->save();

        return back()->with('success', 'Plan de tratamiento actualizado.');
    }

    public function destroy(TreatmentPlan $treatmentPlan): RedirectResponse
    {
        $patientId = $treatmentPlan->patient_id;
        $treatmentPlan->delete();

        return redirect()
            ->route('admin.patients.show', $patientId)
            ->with('success', 'Plan de tratamiento eliminado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'patient_instructions' => ['nullable', 'string'],
            'status' => ['required', Rule::in(TreatmentPlan::STATUSES)],
            'student_profile_id' => ['nullable', Rule::exists('student_profiles', 'id')->whereNull('deleted_at')],
            'teacher_profile_id' => ['nullable', Rule::exists('teacher_profiles', 'id')->whereNull('deleted_at')],
            'started_at' => ['required', 'date'],
            'completed_at' => ['nullable', 'date', 'after_or_equal:started_at'],
        ]);
    }
}
