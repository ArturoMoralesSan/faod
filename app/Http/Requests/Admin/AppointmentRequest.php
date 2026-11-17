<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicPeriod;
use App\Models\Appointment;
use App\Models\ClassSchedule;
use App\Models\SpaceAvailability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Alta, edición y reprogramación de citas.
 */
class AppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Appointment|null $current */
        $current = $this->route('appointment');
        // Al editar se permite conservar una fecha pasada; al crear o reprogramar, no.
        $dateRule = $current && ! $this->routeIs('admin.appointments.reschedule') && $this->input('date') === $current->date?->toDateString()
            ? []
            : ['after_or_equal:today'];

        return [
            'patient_id' => ['required', Rule::exists('patients', 'id')->whereNull('deleted_at')],
            'clinic_id' => ['required', Rule::exists('clinics', 'id')->whereNull('deleted_at')],
            'space_id' => [
                'nullable',
                Rule::exists('spaces', 'id')->where('clinic_id', $this->input('clinic_id'))->whereNull('deleted_at'),
            ],
            'student_profile_id' => ['nullable', Rule::exists('student_profiles', 'id')->whereNull('deleted_at')],
            'teacher_profile_id' => ['nullable', Rule::exists('teacher_profiles', 'id')->whereNull('deleted_at')],
            'treatment_plan_id' => [
                'nullable',
                Rule::exists('treatment_plans', 'id')->where('patient_id', $this->input('patient_id'))->whereNull('deleted_at'),
            ],
            'date' => ['required', 'date', ...$dateRule],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'space_id.exists' => 'El consultorio no pertenece a la clínica seleccionada.',
            'treatment_plan_id.exists' => 'El plan de tratamiento no es de este paciente.',
            'date.after_or_equal' => 'La cita no puede ser en una fecha pasada.',
        ];
    }

    /**
     * Choques de consultorio, alumno y paciente; horario de atención del
     * consultorio y clases programadas en él.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $date = $this->input('date');
                $start = SpaceAvailability::withSeconds($this->input('start_time'));
                $end = SpaceAvailability::withSeconds($this->input('end_time'));
                $current = $this->route('appointment');
                // Al reprogramar, la cita original no cuenta como choque.
                $ignoreId = $current?->id;

                $overlapping = fn () => Appointment::query()
                    ->overlapping($date, $start, $end)
                    ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId));

                if ($this->filled('space_id')) {
                    $spaceId = (int) $this->input('space_id');
                    $periodId = AcademicPeriod::forDate($date)?->id;

                    if ($overlapping()->where('space_id', $spaceId)->exists()) {
                        $validator->errors()->add('space_id', 'El consultorio ya tiene una cita en ese horario.');
                    }

                    if (! SpaceAvailability::coversSlot($spaceId, $periodId, $date, $start, $end)) {
                        $validator->errors()->add('start_time', 'El consultorio no atiende en ese día y horario.');
                    }

                    if ($periodId && ClassSchedule::query()
                        ->overlapping($periodId, Carbon::parse($date)->dayOfWeekIso, $start, $end)
                        ->where('space_id', $spaceId)
                        ->exists()) {
                        $validator->errors()->add('space_id', 'El consultorio tiene una clase programada en ese horario.');
                    }
                }

                if ($this->filled('student_profile_id')
                    && $overlapping()->where('student_profile_id', $this->input('student_profile_id'))->exists()) {
                    $validator->errors()->add('student_profile_id', 'El alumno ya tiene otra cita en ese horario.');
                }

                if ($overlapping()->where('patient_id', $this->input('patient_id'))->exists()) {
                    $validator->errors()->add('start_time', 'El paciente ya tiene otra cita en ese horario.');
                }
            },
        ];
    }
}
