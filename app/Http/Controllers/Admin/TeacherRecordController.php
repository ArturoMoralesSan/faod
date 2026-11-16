<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeacherAvailability;
use App\Models\TeacherCertification;
use App\Models\TeacherDegree;
use App\Models\TeacherExperience;
use App\Models\TeacherProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Registros del expediente docente: grados, certificaciones,
 * experiencia y disponibilidad. Se administran desde la vista del docente.
 *
 * Ruta: admin/teachers/{teacher}/{type}/{record?}
 * type: degrees | certifications | experiences | availabilities
 */
class TeacherRecordController extends Controller
{
    private const MODELS = [
        'degrees' => TeacherDegree::class,
        'certifications' => TeacherCertification::class,
        'experiences' => TeacherExperience::class,
        'availabilities' => TeacherAvailability::class,
    ];

    public function store(Request $request, TeacherProfile $teacher, string $type): RedirectResponse
    {
        $model = $this->modelClass($type);

        $model::create([
            ...$request->validate($this->rules($type)),
            'teacher_profile_id' => $teacher->id,
        ]);

        return back()->with('success', 'Registro agregado correctamente.');
    }

    public function update(Request $request, TeacherProfile $teacher, string $type, int $record): RedirectResponse
    {
        $this->findRecord($teacher, $type, $record)
            ->update($request->validate($this->rules($type)));

        return back()->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy(TeacherProfile $teacher, string $type, int $record): RedirectResponse
    {
        $this->findRecord($teacher, $type, $record)->delete();

        return back()->with('success', 'Registro eliminado correctamente.');
    }

    private function modelClass(string $type): string
    {
        abort_unless(isset(self::MODELS[$type]), 404);

        return self::MODELS[$type];
    }

    private function findRecord(TeacherProfile $teacher, string $type, int $record): Model
    {
        return $this->modelClass($type)::query()
            ->where('teacher_profile_id', $teacher->id)
            ->findOrFail($record);
    }

    private function rules(string $type): array
    {
        return match ($type) {
            'degrees' => [
                'level' => ['required', Rule::in(TeacherDegree::LEVELS)],
                'title' => ['required', 'string', 'max:255'],
                'institution' => ['required', 'string', 'max:255'],
                'license_number' => ['nullable', 'string', 'max:50'],
                'obtained_at' => ['nullable', 'date', 'before_or_equal:today'],
            ],
            'certifications' => [
                'name' => ['required', 'string', 'max:255'],
                'issuer' => ['nullable', 'string', 'max:255'],
                'folio' => ['nullable', 'string', 'max:100'],
                'issued_at' => ['nullable', 'date'],
                'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            ],
            'experiences' => [
                'institution' => ['required', 'string', 'max:255'],
                'position' => ['required', 'string', 'max:255'],
                'started_at' => ['nullable', 'date'],
                'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
                'description' => ['nullable', 'string'],
            ],
            'availabilities' => [
                'academic_period_id' => ['nullable', 'exists:academic_periods,id'],
                'day_of_week' => ['required', 'integer', 'between:1,7'],
                'start_time' => ['required', 'date_format:H:i'],
                'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            ],
        };
    }
}
