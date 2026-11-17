<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicPeriod;
use App\Models\ClassSchedule;
use App\Models\ClinicalSession;
use App\Models\SpaceAvailability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ClinicalSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sin periodo indicado, se usa el periodo académico que contiene la fecha.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('academic_period_id') || ! $this->filled('date')) {
            return;
        }

        $this->merge(['academic_period_id' => AcademicPeriod::forDate($this->input('date'))?->id]);
    }

    public function rules(): array
    {
        return [
            'clinic_id' => ['required', Rule::exists('clinics', 'id')->whereNull('deleted_at')],
            'space_id' => [
                'nullable',
                Rule::exists('spaces', 'id')
                    ->where('clinic_id', $this->input('clinic_id'))
                    ->whereNull('deleted_at'),
            ],
            'academic_period_id' => ['nullable', 'exists:academic_periods,id'],
            'academic_group_id' => ['nullable', 'exists:academic_groups,id'],
            'subject_id' => ['nullable', Rule::exists('subjects', 'id')->whereNull('deleted_at')],
            'supervisor_id' => ['nullable', Rule::exists('teacher_profiles', 'id')->whereNull('deleted_at')],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', Rule::in(ClinicalSession::STATUSES)],
            'topic' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'space_id.exists' => 'El consultorio no pertenece a la clínica seleccionada.',
        ];
    }

    /**
     * Evita choques de consultorio y supervisor, y respeta la disponibilidad
     * del consultorio y las clases ya programadas en él.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty() || $this->input('status') === 'cancelada') {
                    return;
                }

                $date = $this->input('date');
                // Con segundos, para comparar igual que las columnas TIME (HH:MM:SS).
                $start = SpaceAvailability::withSeconds($this->input('start_time'));
                $end = SpaceAvailability::withSeconds($this->input('end_time'));
                $spaceId = $this->input('space_id');
                $supervisorId = $this->input('supervisor_id');
                $periodId = $this->input('academic_period_id') ? (int) $this->input('academic_period_id') : null;
                $dayOfWeek = Carbon::parse($date)->dayOfWeekIso;
                $current = $this->route('clinical_session');

                $overlapping = fn () => ClinicalSession::query()
                    ->overlapping($date, $start, $end)
                    ->when($current, fn ($q) => $q->whereKeyNot($current->id));

                if ($spaceId) {
                    if ($overlapping()->where('space_id', $spaceId)->exists()) {
                        $validator->errors()->add('space_id', 'El consultorio ya tiene otra sesión clínica en ese horario.');
                    }

                    if ($periodId && ClassSchedule::query()
                        ->overlapping($periodId, $dayOfWeek, $start, $end)
                        ->where('space_id', $spaceId)
                        ->exists()) {
                        $validator->errors()->add('space_id', 'El consultorio tiene una clase programada en ese horario.');
                    }

                    // Si el consultorio tiene horario de atención definido, la sesión debe caber en él.
                    if (! SpaceAvailability::coversSlot((int) $spaceId, $periodId, $date, $start, $end)) {
                        $validator->errors()->add('start_time', 'El consultorio no está disponible en ese día y horario.');
                    }
                }

                if ($supervisorId && $overlapping()->where('supervisor_id', $supervisorId)->exists()) {
                    $validator->errors()->add('supervisor_id', 'El supervisor ya tiene otra sesión clínica en ese horario.');
                }
            },
        ];
    }
}
