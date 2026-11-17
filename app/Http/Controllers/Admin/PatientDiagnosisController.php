<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OdontogramEntry;
use App\Models\Patient;
use App\Models\PatientDiagnosis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Diagnósticos de un paciente (se administran desde el expediente).
 * Cualquier cambio vuelve a requerir la autorización del docente.
 */
class PatientDiagnosisController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $patient->diagnoses()->create([
            ...$this->validated($request, $patient),
            'review_status' => 'pendiente',
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Diagnóstico registrado; queda pendiente de revisión docente.');
    }

    public function update(Request $request, Patient $patient, PatientDiagnosis $diagnosis): RedirectResponse
    {
        abort_unless($diagnosis->patient_id === $patient->id, 404);

        $diagnosis->fill($this->validated($request, $patient));

        if ($diagnosis->isDirty()) {
            $diagnosis->review_status = 'pendiente';
        }

        $diagnosis->save();

        return back()->with('success', 'Diagnóstico actualizado; queda pendiente de revisión docente.');
    }

    public function destroy(Patient $patient, PatientDiagnosis $diagnosis): RedirectResponse
    {
        abort_unless($diagnosis->patient_id === $patient->id, 404);

        $diagnosis->delete();

        return back()->with('success', 'Diagnóstico eliminado.');
    }

    private function validated(Request $request, Patient $patient): array
    {
        $data = $request->validate([
            'patient_consultation_id' => [
                'nullable',
                Rule::exists('patient_consultations', 'id')->where('patient_id', $patient->id),
            ],
            'diagnosis_id' => ['nullable', 'required_without:description', Rule::exists('diagnoses', 'id')],
            'description' => ['nullable', 'required_without:diagnosis_id', 'string', 'max:255'],
            'tooth' => ['nullable', Rule::in(OdontogramEntry::teeth())],
            'surfaces' => ['nullable', 'string', 'max:5', 'regex:/^[OMDVL]+$/'],
            'type' => ['required', Rule::in(PatientDiagnosis::TYPES)],
            'diagnosed_at' => ['required', 'date', 'before_or_equal:today'],
            'student_profile_id' => ['nullable', Rule::exists('student_profiles', 'id')->whereNull('deleted_at')],
            'teacher_profile_id' => ['nullable', Rule::exists('teacher_profiles', 'id')->whereNull('deleted_at')],
            'notes' => ['nullable', 'string'],
        ], [
            'diagnosis_id.required_without' => 'Elige un diagnóstico del catálogo o escribe una descripción.',
            'description.required_without' => 'Elige un diagnóstico del catálogo o escribe una descripción.',
            'surfaces.regex' => 'Usa las letras de las caras: O, M, D, V, L.',
        ]);

        // Caras sin repetir y en la notación habitual (p. ej. "DOM" → "MOD").
        if (! empty($data['surfaces'])) {
            $data['surfaces'] = implode('', array_values(array_intersect(['M', 'O', 'D', 'V', 'L'], str_split($data['surfaces']))));
        }

        return $data;
    }
}
