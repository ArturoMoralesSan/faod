<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientCondition;
use App\Models\PatientConsultation;
use App\Models\PatientEmergencyContact;
use App\Models\PatientMedication;
use App\Models\PatientProgressNote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Registros del expediente: contactos, antecedentes, alergias, medicamentos,
 * consultas y notas de evolución. Se administran desde la vista del paciente.
 *
 * Ruta: admin/patients/{patient}/{type}/{record?}
 */
class PatientRecordController extends Controller
{
    public const TYPES = ['contacts', 'conditions', 'allergies', 'medications', 'consultations', 'notes'];

    private const MODELS = [
        'contacts' => PatientEmergencyContact::class,
        'conditions' => PatientCondition::class,
        'allergies' => PatientAllergy::class,
        'medications' => PatientMedication::class,
        'consultations' => PatientConsultation::class,
        'notes' => PatientProgressNote::class,
    ];

    /** Registros que guardan quién los capturó. */
    private const WITH_AUTHOR = ['consultations', 'notes'];

    public function store(Request $request, Patient $patient, string $type): RedirectResponse
    {
        $model = $this->modelClass($type);

        $model::create([
            ...$request->validate($this->rules($patient, $type)),
            'patient_id' => $patient->id,
            ...(in_array($type, self::WITH_AUTHOR, true) ? ['created_by' => $request->user()->id] : []),
        ]);

        return back()->with('success', 'Registro agregado al expediente.');
    }

    public function update(Request $request, Patient $patient, string $type, int $record): RedirectResponse
    {
        $this->findRecord($patient, $type, $record)
            ->update($request->validate($this->rules($patient, $type)));

        return back()->with('success', 'Registro actualizado.');
    }

    public function destroy(Patient $patient, string $type, int $record): RedirectResponse
    {
        $this->findRecord($patient, $type, $record)->delete();

        return back()->with('success', 'Registro eliminado.');
    }

    private function modelClass(string $type): string
    {
        abort_unless(isset(self::MODELS[$type]), 404);

        return self::MODELS[$type];
    }

    private function findRecord(Patient $patient, string $type, int $record): Model
    {
        return $this->modelClass($type)::query()
            ->where('patient_id', $patient->id)
            ->findOrFail($record);
    }

    private function rules(Patient $patient, string $type): array
    {
        $student = ['nullable', Rule::exists('student_profiles', 'id')->whereNull('deleted_at')];
        $teacher = ['nullable', Rule::exists('teacher_profiles', 'id')->whereNull('deleted_at')];

        return match ($type) {
            'contacts' => [
                'name' => ['required', 'string', 'max:255'],
                'relationship' => ['nullable', 'string', 'max:50'],
                'phone' => ['required', 'string', 'max:20'],
                'alternate_phone' => ['nullable', 'string', 'max:20'],
            ],
            'conditions' => [
                'category' => ['required', Rule::in(PatientCondition::CATEGORIES)],
                'name' => ['required', 'string', 'max:255'],
                'notes' => ['nullable', 'string'],
            ],
            'allergies' => [
                'allergen' => ['required', 'string', 'max:255'],
                'reaction' => ['nullable', 'string', 'max:255'],
                'severity' => ['required', Rule::in(PatientAllergy::SEVERITIES)],
            ],
            'medications' => [
                'name' => ['required', 'string', 'max:255'],
                'dose' => ['nullable', 'string', 'max:255'],
                'frequency' => ['nullable', 'string', 'max:255'],
                'started_at' => ['nullable', 'date'],
                'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
                'notes' => ['nullable', 'string'],
            ],
            'consultations' => [
                'clinic_id' => ['nullable', Rule::exists('clinics', 'id')->whereNull('deleted_at')],
                'clinical_session_id' => ['nullable', Rule::exists('clinical_sessions', 'id')->whereNull('deleted_at')],
                'student_profile_id' => $student,
                'teacher_profile_id' => $teacher,
                'attended_at' => ['required', 'date', 'before_or_equal:now'],
                'reason' => ['required', 'string'],
                'current_illness' => ['nullable', 'string'],
                'symptoms' => ['nullable', 'array'],
                'symptoms.*' => ['string', Rule::in(PatientConsultation::symptomKeys())],
                'pain_intensity' => ['nullable', 'integer', 'between:0,10'],
                'blood_pressure' => ['nullable', 'string', 'max:20', 'regex:/^\d{2,3}\/\d{2,3}$/'],
                'heart_rate' => ['nullable', 'integer', 'between:20,250'],
                'respiratory_rate' => ['nullable', 'integer', 'between:5,80'],
                'temperature' => ['nullable', 'numeric', 'between:30,45'],
                'weight' => ['nullable', 'numeric', 'between:1,400'],
                'height' => ['nullable', 'numeric', 'between:0.3,2.5'],
                'findings' => ['nullable', 'string'],
                'notes' => ['nullable', 'string'],
            ],
            'notes' => [
                'patient_consultation_id' => [
                    'nullable',
                    Rule::exists('patient_consultations', 'id')->where('patient_id', $patient->id),
                ],
                'student_profile_id' => $student,
                'teacher_profile_id' => $teacher,
                'noted_at' => ['required', 'date', 'before_or_equal:now'],
                'note' => ['required', 'string'],
            ],
        };
    }
}
