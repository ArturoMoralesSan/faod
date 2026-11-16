<?php

namespace App\Http\Requests\Admin;

use App\Models\Cohort;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CohortRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_program_id' => ['required', 'exists:academic_programs,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cohorts', 'name')
                    ->where('academic_program_id', $this->input('academic_program_id'))
                    ->ignore($this->route('cohort')),
            ],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'status' => ['required', Rule::in(Cohort::STATUSES)],
        ];
    }
}
