<?php

namespace App\Http\Requests\Admin;

use App\Models\StudentEnrollment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_profile_id' => ['required', 'exists:student_profiles,id'],
            'academic_program_id' => ['required', 'exists:academic_programs,id'],
            'cohort_id' => [
                'nullable',
                Rule::exists('cohorts', 'id')->where('academic_program_id', $this->input('academic_program_id')),
            ],
            'study_plan_id' => [
                'nullable',
                Rule::exists('study_plans', 'id')->where('academic_program_id', $this->input('academic_program_id')),
            ],
            'status' => ['required', Rule::in(StudentEnrollment::STATUSES)],
            'started_at' => ['nullable', 'date'],
            'withdrawn_at' => ['nullable', 'required_if:status,baja', 'date'],
            'withdrawal_reason' => ['nullable', 'required_if:status,baja', 'string'],
            'graduated_at' => ['nullable', 'required_if:status,egresado', 'date'],
            'notes' => ['nullable', 'string'],

            // Motivo del cambio de situación (para el historial)
            'status_reason' => ['nullable', 'string'],
        ];
    }
}
