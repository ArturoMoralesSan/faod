<?php

namespace App\Http\Requests\Admin;

use App\Models\ClinicIncident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClinicIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clinicId = $this->input('clinic_id');

        return [
            'clinic_id' => ['required', Rule::exists('clinics', 'id')->whereNull('deleted_at')],
            'space_id' => [
                'nullable',
                Rule::exists('spaces', 'id')->where('clinic_id', $clinicId)->whereNull('deleted_at'),
            ],
            'clinic_equipment_id' => [
                'nullable',
                Rule::exists('clinic_equipment', 'id')->where('clinic_id', $clinicId)->whereNull('deleted_at'),
            ],
            'clinical_session_id' => [
                'nullable',
                Rule::exists('clinical_sessions', 'id')->where('clinic_id', $clinicId)->whereNull('deleted_at'),
            ],
            'type' => ['required', Rule::in(ClinicIncident::TYPES)],
            'severity' => ['required', Rule::in(ClinicIncident::SEVERITIES)],
            'status' => ['required', Rule::in(ClinicIncident::STATUSES)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
            'resolution' => [
                Rule::requiredIf(in_array($this->input('status'), ClinicIncident::CLOSED_STATUSES, true)),
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'resolution.required' => 'Describe cómo se resolvió la incidencia.',
            'space_id.exists' => 'El espacio no pertenece a la clínica seleccionada.',
            'clinic_equipment_id.exists' => 'El equipo no pertenece a la clínica seleccionada.',
            'clinical_session_id.exists' => 'La sesión no pertenece a la clínica seleccionada.',
        ];
    }
}
