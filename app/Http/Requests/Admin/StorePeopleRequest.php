<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePeopleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'curp' => ['nullable', 'string', 'size:18', 'unique:people,curp'],
            'rfc' => ['nullable', 'string', 'size:13', 'unique:people,rfc'],

            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'paternal_surname' => ['required', 'string', 'max:100'],
            'maternal_surname' => ['nullable', 'string', 'max:100'],

            'birth_date' => ['nullable', 'date', 'before:today'],
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
            'country' => ['nullable', 'string', 'max:100'],

            // Cuenta de acceso opcional
            'account' => ['nullable', 'array'],
            'account.email' => [
                'required_with:account',
                'nullable',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'account.password' => [
                'required_with:account',
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
            'account.password_confirmation' => [
                'required_with:account.password',
                'nullable',
                'string',
            ],

            // Perfil de alumno
            'student_profile' => ['nullable', 'array'],
            'student_profile.registration_number' => [
                'required_with:student_profile',
                'nullable',
                'string',
                'max:50',
                'unique:student_profiles,registration_number',
            ],
            'student_profile.status' => [
                'nullable',
                Rule::in(['active', 'inactive', 'suspended', 'graduated']),
            ],
            'student_profile.admission_date' => ['nullable', 'date'],
            'student_profile.internal_notes' => ['nullable', 'string'],

            // Perfil docente
            'teacher_profile' => ['nullable', 'array'],
            'teacher_profile.employee_number' => [
                'nullable',
                'string',
                'max:50',
                'unique:teacher_profiles,employee_number',
            ],
            'teacher_profile.professional_license' => [
                'nullable',
                'string',
                'max:50',
                'unique:teacher_profiles,professional_license',
            ],
            'teacher_profile.professional_title' => ['nullable', 'string', 'max:255'],
            'teacher_profile.highest_degree' => ['nullable', 'string', 'max:255'],
            'teacher_profile.specialty' => ['nullable', 'string', 'max:255'],
            'teacher_profile.years_experience' => ['nullable', 'integer', 'min:0', 'max:100'],
            'teacher_profile.status' => [
                'nullable',
                Rule::in(['active', 'inactive']),
            ],
            'teacher_profile.professional_biography' => ['nullable', 'string'],

            // Perfil administrativo
            'staff_profile' => ['nullable', 'array'],
            'staff_profile.employee_number' => [
                'nullable',
                'string',
                'max:50',
                'unique:staff_profiles,employee_number',
            ],
            'staff_profile.position' => ['required_with:staff_profile', 'nullable', 'string', 'max:255'],
            'staff_profile.department' => ['nullable', 'string', 'max:255'],
            'staff_profile.department_id' => ['nullable', 'exists:departments,id'],
            'staff_profile.status' => [
                'nullable',
                Rule::in(['active', 'inactive']),
            ],
            'staff_profile.hired_at' => ['nullable', 'date'],
            'staff_profile.notes' => ['nullable', 'string'],
        ];
    }
}
