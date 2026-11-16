<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicProgram;
use App\Models\ProgramObjective;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => ['nullable', 'exists:departments,id'],
            'specialty_id' => ['nullable', 'exists:specialties,id'],
            'type' => ['required', Rule::in(AcademicProgram::TYPES)],
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('academic_programs', 'code')->ignore($this->route('academic_program')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_value' => ['nullable', 'integer', 'min:1', 'max:120'],
            'duration_unit' => ['nullable', 'required_with:duration_value', Rule::in(AcademicProgram::DURATION_UNITS)],
            'total_periods' => ['nullable', 'integer', 'min:1', 'max:30'],
            'is_active' => ['boolean'],

            // Objetivos y competencias (se sincronizan completos)
            'objectives' => ['nullable', 'array'],
            'objectives.*.type' => ['required', Rule::in(ProgramObjective::TYPES)],
            'objectives.*.description' => ['required', 'string'],

            // Docentes y coordinadores (se sincronizan completos)
            'teachers' => ['nullable', 'array'],
            'teachers.*.teacher_profile_id' => ['required', 'exists:teacher_profiles,id'],
            'teachers.*.role' => ['required', Rule::in(AcademicProgram::TEACHER_ROLES)],
            'teachers.*.starts_at' => ['nullable', 'date'],
            'teachers.*.ends_at' => ['nullable', 'date', 'after_or_equal:teachers.*.starts_at'],
        ];
    }
}
