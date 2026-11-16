<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudyPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_program_id' => ['required', 'exists:academic_programs,id'],
            'name' => ['required', 'string', 'max:255'],
            'version' => [
                'required',
                'string',
                'max:20',
                Rule::unique('study_plans', 'version')
                    ->where('academic_program_id', $this->input('academic_program_id'))
                    ->ignore($this->route('study_plan')),
            ],
            'valid_from' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
