<?php

namespace App\Http\Requests\Admin;

use App\Models\Space;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SpaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clinic_id' => ['nullable', 'exists:clinics,id'],
            'clinic_area_id' => [
                'nullable',
                // El área debe ser de la clínica seleccionada.
                Rule::exists('clinic_areas', 'id')->where('clinic_id', $this->input('clinic_id')),
            ],
            'type' => ['required', Rule::in(Space::TYPES)],
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('spaces', 'code')->ignore($this->route('space')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'building' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:20'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ];
    }
}
