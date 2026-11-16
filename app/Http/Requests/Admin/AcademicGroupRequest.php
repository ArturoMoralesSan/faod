<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cohort_id' => ['required', 'exists:cohorts,id'],
            'academic_period_id' => ['required', 'exists:academic_periods,id'],
            'period_number' => ['required', 'integer', 'min:1', 'max:30'],
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('academic_groups', 'code')
                    ->where('academic_period_id', $this->input('academic_period_id'))
                    ->ignore($this->route('group')),
            ],
            'capacity' => ['nullable', 'integer', 'min:1'],

            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:student_profiles,id'],
        ];
    }
}
