<?php

namespace App\Http\Requests\Admin;

use App\Models\ClassSchedule;
use App\Models\ClassSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ClassScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'class_section_id' => ['required', 'exists:class_sections,id'],
            'space_id' => ['nullable', 'exists:spaces,id'],
            'day_of_week' => ['required', 'integer', Rule::in(array_keys(ClassSchedule::DAYS))],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }

    /**
     * Evita choques de horario de aula, docente y grupo en el mismo periodo.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $section = ClassSection::with('group')->find($this->input('class_section_id'));
                $current = $this->route('class_schedule');

                $overlapping = fn () => ClassSchedule::query()
                    ->overlapping(
                        $section->group->academic_period_id,
                        (int) $this->input('day_of_week'),
                        $this->input('start_time'),
                        $this->input('end_time'),
                    )
                    ->when($current, fn ($q) => $q->whereKeyNot($current->id));

                if ($this->filled('space_id')
                    && $overlapping()->where('space_id', $this->input('space_id'))->exists()) {
                    $validator->errors()->add('space_id', 'El espacio ya está ocupado en ese horario.');
                }

                if ($section->teacher_profile_id
                    && $overlapping()->whereHas(
                        'classSection',
                        fn ($q) => $q->where('teacher_profile_id', $section->teacher_profile_id)
                    )->exists()) {
                    $validator->errors()->add('start_time', 'El docente ya tiene clase en ese horario.');
                }

                if ($overlapping()->whereHas(
                    'classSection',
                    fn ($q) => $q->where('academic_group_id', $section->academic_group_id)
                )->exists()) {
                    $validator->errors()->add('start_time', 'El grupo ya tiene otra materia en ese horario.');
                }
            },
        ];
    }
}
