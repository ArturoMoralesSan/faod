<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PatientRequest;
use App\Models\Clinic;
use App\Models\ClinicalSession;
use App\Models\Diagnosis;
use App\Models\OdontogramEntry;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientCondition;
use App\Models\PatientConsultation;
use App\Models\PatientDiagnosis;
use App\Models\People;
use App\Models\Specialty;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Support\TeacherScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Expediente clínico de pacientes.
 */
class PatientController extends Controller
{
    public function index(Request $request): Response
    {
        $patients = Patient::query()
            ->tap(fn ($q) => TeacherScope::apply($q, TeacherScope::teacherId($request->user())))
            ->with([
                'people:id,first_name,middle_name,paternal_surname,maternal_surname,phone,birth_date,curp',
                'clinic:id,name',
            ])
            ->withCount('allergies')
            ->when($request->search, fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('record_number', 'like', "%{$search}%")
                ->orWhereHas('people', fn ($q) => $q->search($search))))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->clinic_id, fn ($q, $id) => $q->where('clinic_id', $id))
            ->when($request->integer('student_profile_id'), fn ($q, $id) => $q->attendedBy($id))
            ->when($request->integer('specialty_id'), fn ($q, $id) => $q->ofSpecialty($id))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/patients/Index', [
            'patients' => $patients,
            'filters' => $request->only('search', 'status', 'clinic_id', 'student_profile_id', 'specialty_id'),
            'statuses' => Patient::STATUSES,
            'clinics' => Clinic::orderBy('name')->get(['id', 'name']),
            'students' => StudentProfile::query()
                ->whereHas('enrollments', fn ($q) => $q->where('status', 'activo'))
                ->with('people:id,first_name,paternal_surname,maternal_surname')
                ->get(['id', 'people_id', 'registration_number'])
                ->map(fn ($s) => ['value' => $s->id, 'label' => "{$s->registration_number} · {$s->people?->full_name}"])
                ->sortBy('label')
                ->values(),
            'specialties' => Specialty::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Búsqueda para selectores (agenda, pagos…): JSON con {id, label}.
     */
    public function search(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q'));

        if (mb_strlen($search) < 2) {
            return response()->json([]);
        }

        return response()->json(
            Patient::query()
                ->with('people:id,first_name,middle_name,paternal_surname,maternal_surname,phone')
                ->where('status', '!=', 'baja')
                ->where(fn ($q) => $q
                    ->where('record_number', 'like', "%{$search}%")
                    ->orWhereHas('people', fn ($q) => $q->search($search)))
                ->limit(15)
                ->get()
                ->map(fn (Patient $patient) => [
                    'id' => $patient->id,
                    'label' => "{$patient->record_number} · {$patient->people->full_name}",
                    'hint' => $patient->people->phone,
                    'clinic_id' => $patient->clinic_id,
                ])
        );
    }

    public function create(): Response
    {
        return Inertia::render('admin/patients/Create', $this->formData());
    }

    public function store(PatientRequest $request): RedirectResponse
    {
        $patient = DB::transaction(function () use ($request) {
            $personData = $request->personData();

            // Si la CURP ya está registrada (p. ej. un alumno), se usa esa persona.
            $people = ! empty($personData['curp'])
                ? People::where('curp', $personData['curp'])->first()
                : null;

            if ($people?->patient()->exists()) {
                throw ValidationException::withMessages([
                    'curp' => 'Esta persona ya tiene expediente: '.$people->patient->record_number,
                ]);
            }

            if ($people) {
                // Solo se completan los datos que la persona no tenía.
                $people->fill(collect($personData)->filter(fn ($value, $key) => blank($people->{$key}) && filled($value))->all())->save();
            } else {
                $people = People::create($personData);
            }

            return Patient::create([
                ...$request->patientData(),
                'people_id' => $people->id,
                'created_by' => $request->user()->id,
            ]);
        });

        return redirect()
            ->route('admin.patients.show', $patient)
            ->with('success', "Expediente {$patient->record_number} creado correctamente.");
    }

    public function show(Patient $patient): Response
    {
        TeacherScope::ensureVisible($patient, request()->user());

        $teacherPeople = 'teacher.people:id,first_name,paternal_surname,maternal_surname';
        $studentPeople = 'student.people:id,first_name,paternal_surname,maternal_surname';

        $patient->load([
            'people',
            'clinic:id,name',
            'creator:id,name',
            'emergencyContacts',
            'conditions',
            'allergies',
            'medications',
            'consultations' => fn ($q) => $q->with(['clinic:id,name', $studentPeople, $teacherPeople]),
            'progressNotes' => fn ($q) => $q->with([$studentPeople, $teacherPeople, 'creator:id,name']),
            'diagnoses' => fn ($q) => $q->with([
                'diagnosis:id,code,name',
                $studentPeople,
                $teacherPeople,
                'reviews.reviewer:id,name',
            ]),
            'odontogramEntries' => fn ($q) => $q->with('creator:id,name'),
            'appointments' => fn ($q) => $q
                ->whereDate('date', '>=', today())
                ->whereIn('status', ['programada', 'confirmada'])
                ->with(['clinic:id,name', 'space:id,code,name', $studentPeople]),
            'treatmentPlans' => fn ($q) => $q
                ->with($teacherPeople)
                ->withCount([
                    'procedures',
                    'procedures as done_count' => fn ($q) => $q->where('status', 'realizado'),
                ])
                ->withSum(['procedures as total_cost' => fn ($q) => $q->where('status', '!=', 'cancelado')], 'cost'),
            'attachments',
        ]);

        return Inertia::render('admin/patients/Show', [
            'patient' => $patient,
            'diagnosisCatalog' => Diagnosis::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name', 'category']),
            'diagnosisTypes' => PatientDiagnosis::TYPES,
            'odontogram' => [
                'surfaces' => OdontogramEntry::SURFACES,
                'surfaceConditions' => OdontogramEntry::SURFACE_CONDITIONS,
                'toothConditions' => OdontogramEntry::TOOTH_CONDITIONS,
                'permanentTeeth' => OdontogramEntry::PERMANENT_TEETH,
                'primaryTeeth' => OdontogramEntry::PRIMARY_TEETH,
            ],
            'can' => [
                'diagnose' => request()->user()->can('diagnoses.create'),
                'treat' => request()->user()->can('treatments.create'),
                'schedule' => request()->user()->can('appointments.create'),
                'billing' => request()->user()->can('charges.view'),
                'review' => request()->user()->can('reviews.create'),
            ],
            'conditionCategories' => PatientCondition::CATEGORIES,
            'allergySeverities' => PatientAllergy::SEVERITIES,
            'symptomGroups' => PatientConsultation::SYMPTOMS,
            'clinics' => Clinic::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'sessions' => ClinicalSession::whereDate('date', '>=', now()->subDays(30))
                ->where('status', '!=', 'cancelada')
                ->orderByDesc('date')
                ->get(['id', 'clinic_id', 'date', 'start_time', 'topic']),
            'students' => StudentProfile::query()
                ->whereHas('enrollments', fn ($q) => $q->where('status', 'activo'))
                ->with('people:id,first_name,paternal_surname,maternal_surname')
                ->get(['id', 'people_id', 'registration_number']),
            'teachers' => TeacherProfile::where('status', 'active')
                ->with('people:id,first_name,paternal_surname,maternal_surname')
                ->get(['id', 'people_id']),
        ]);
    }

    public function edit(Patient $patient): Response
    {
        return Inertia::render('admin/patients/Edit', [
            'patient' => $patient->load('people'),
            ...$this->formData(),
        ]);
    }

    public function update(PatientRequest $request, Patient $patient): RedirectResponse
    {
        DB::transaction(function () use ($request, $patient) {
            $patient->people->update($request->personData());
            $patient->update($request->patientData());
        });

        return redirect()
            ->route('admin.patients.show', $patient)
            ->with('success', 'Expediente actualizado correctamente.');
    }

    /**
     * Elimina el expediente (la persona se conserva por si es alumno, docente, etc.).
     */
    public function destroy(Patient $patient): RedirectResponse
    {
        $patient->delete();

        return redirect()
            ->route('admin.patients.index')
            ->with('success', 'Expediente eliminado correctamente.');
    }

    private function formData(): array
    {
        return [
            'clinics' => Clinic::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'statuses' => Patient::STATUSES,
            'bloodTypes' => Patient::BLOOD_TYPES,
            'maritalStatuses' => Patient::MARITAL_STATUSES,
        ];
    }
}
