<?php

namespace App\Http\Requests\Admin;

use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Datos personales (people) y del expediente (patients) en un solo formulario.
 */
class PatientRequest extends FormRequest
{
    public const PERSON_FIELDS = [
        'curp', 'first_name', 'middle_name', 'paternal_surname', 'maternal_surname',
        'birth_date', 'gender', 'phone', 'personal_email',
        'street', 'exterior_number', 'interior_number', 'neighborhood', 'postal_code', 'city', 'state',
    ];

    public const PATIENT_FIELDS = [
        'clinic_id', 'blood_type', 'occupation', 'marital_status', 'referred_by', 'status', 'registered_at', 'notes',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('curp')) {
            $this->merge(['curp' => strtoupper(trim($this->input('curp')))]);
        }
    }

    public function rules(): array
    {
        /** @var Patient|null $patient */
        $patient = $this->route('patient');

        return [
            // En alta, una CURP existente se liga a esa persona (validado en el controlador).
            'curp' => array_filter([
                'nullable',
                'string',
                'size:18',
                $patient ? Rule::unique('people', 'curp')->ignore($patient->people_id) : null,
            ]),
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'paternal_surname' => ['required', 'string', 'max:100'],
            'maternal_surname' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:20'],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'exterior_number' => ['nullable', 'string', 'max:20'],
            'interior_number' => ['nullable', 'string', 'max:20'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],

            'clinic_id' => ['nullable', Rule::exists('clinics', 'id')->whereNull('deleted_at')],
            'blood_type' => ['nullable', Rule::in(Patient::BLOOD_TYPES)],
            'occupation' => ['nullable', 'string', 'max:255'],
            'marital_status' => ['nullable', Rule::in(Patient::MARITAL_STATUSES)],
            'referred_by' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(Patient::STATUSES)],
            'registered_at' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function personData(): array
    {
        return collect($this->validated())->only(self::PERSON_FIELDS)->all();
    }

    public function patientData(): array
    {
        return collect($this->validated())->only(self::PATIENT_FIELDS)->all();
    }
}
