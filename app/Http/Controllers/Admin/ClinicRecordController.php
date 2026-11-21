<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\ClinicArea;
use App\Models\ClinicEquipment;
use App\Models\ClinicSupervisor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Registros de una clínica: áreas, supervisores y equipos.
 * Se administran desde la vista de la clínica.
 *
 * Ruta: admin/clinics/{clinic}/{type}/{record?}
 * type: areas | supervisors | equipment
 */
class ClinicRecordController extends Controller
{
    public const TYPES = ['areas', 'supervisors', 'equipment'];

    private const MODELS = [
        'areas' => ClinicArea::class,
        'supervisors' => ClinicSupervisor::class,
        'equipment' => ClinicEquipment::class,
    ];

    public function store(Request $request, Clinic $clinic, string $type): RedirectResponse
    {
        $model = $this->modelClass($type);

        $model::create([
            ...$request->validate($this->rules($clinic, $type)),
            'clinic_id' => $clinic->id,
        ]);

        return back()->with('success', 'Registro agregado correctamente.');
    }

    public function update(Request $request, Clinic $clinic, string $type, int $record): RedirectResponse
    {
        $model = $this->findRecord($clinic, $type, $record);

        $model->update($request->validate($this->rules($clinic, $type, $model)));

        return back()->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy(Clinic $clinic, string $type, int $record): RedirectResponse
    {
        $this->findRecord($clinic, $type, $record)->delete();

        return back()->with('success', 'Registro eliminado correctamente.');
    }

    private function modelClass(string $type): string
    {
        abort_unless(isset(self::MODELS[$type]), 404);

        return self::MODELS[$type];
    }

    private function findRecord(Clinic $clinic, string $type, int $record): Model
    {
        return $this->modelClass($type)::query()
            ->where('clinic_id', $clinic->id)
            ->findOrFail($record);
    }

    private function rules(Clinic $clinic, string $type, ?Model $record = null): array
    {
        // Áreas y espacios deben pertenecer a la misma clínica.
        $clinicArea = Rule::exists('clinic_areas', 'id')->where('clinic_id', $clinic->id);
        $clinicSpace = Rule::exists('spaces', 'id')->where('clinic_id', $clinic->id)->whereNull('deleted_at');

        return match ($type) {
            'areas' => [
                'specialty_id' => ['nullable', Rule::exists('specialties', 'id')],
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('clinic_areas', 'name')
                        ->where('clinic_id', $clinic->id)
                        ->ignore($record),
                ],
                'description' => ['nullable', 'string'],
                'is_active' => ['boolean'],
            ],
            'supervisors' => [
                'teacher_profile_id' => ['required', Rule::exists('teacher_profiles', 'id')->whereNull('deleted_at')],
                'clinic_area_id' => ['nullable', $clinicArea],
                'role' => ['required', Rule::in(ClinicSupervisor::ROLES)],
                'starts_at' => ['nullable', 'date'],
                'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            ],
            'equipment' => [
                'kind' => ['sometimes', 'required', Rule::in(ClinicEquipment::KINDS)],
                'space_id' => ['nullable', $clinicSpace],
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('clinic_equipment', 'code')->ignore($record),
                ],
                'name' => ['required', 'string', 'max:255'],
                'brand' => ['nullable', 'string', 'max:255'],
                'model' => ['nullable', 'string', 'max:255'],
                'serial_number' => ['nullable', 'string', 'max:255'],
                'status' => ['required', Rule::in(ClinicEquipment::STATUSES)],
                'acquired_at' => ['nullable', 'date', 'before_or_equal:today'],
                'last_maintenance_at' => ['nullable', 'date', 'before_or_equal:today'],
                'next_maintenance_at' => ['nullable', 'date'],
                'notes' => ['nullable', 'string'],
            ],
        };
    }
}
