<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentEnrollment;
use App\Models\UniversityRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Situación de inscripción ante la universidad, por periodo.
 * El comprobante se sube con AttachmentController (attachable_type = university_registration).
 */
class UniversityRegistrationController extends Controller
{
    public function store(Request $request, StudentEnrollment $student): RedirectResponse
    {
        $data = $request->validate([
            'academic_period_id' => [
                'required',
                'exists:academic_periods,id',
                Rule::unique('university_registrations')->where('student_enrollment_id', $student->id),
            ],
            ...$this->rules(),
        ]);

        $student->universityRegistrations()->create($this->withVerifier($request, $data));

        return back()->with('success', 'Situación de inscripción registrada.');
    }

    public function update(Request $request, UniversityRegistration $registration): RedirectResponse
    {
        $data = $request->validate($this->rules());

        $registration->update($this->withVerifier($request, $data));

        return back()->with('success', 'Situación de inscripción actualizada.');
    }

    public function destroy(UniversityRegistration $registration): RedirectResponse
    {
        $registration->delete();

        return back()->with('success', 'Registro eliminado.');
    }

    private function rules(): array
    {
        return [
            'status' => ['required', Rule::in(UniversityRegistration::STATUSES)],
            'registered_at' => ['nullable', 'required_if:status,inscrito', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function withVerifier(Request $request, array $data): array
    {
        $data['verified_by'] = $data['status'] === 'pendiente' ? null : $request->user()->id;

        return $data;
    }
}
