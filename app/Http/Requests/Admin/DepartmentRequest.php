<?php

namespace App\Http\Requests\Admin;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $department = $this->route('department');

        return [
            'parent_id' => [
                'nullable',
                'exists:departments,id',
                Rule::notIn([$department?->id]),
            ],
            'type' => ['required', Rule::in(Department::TYPES)],
            'code' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('departments', 'code')->ignore($department),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'head_people_id' => ['nullable', 'exists:people,id'],
            'is_active' => ['boolean'],
        ];
    }
}
