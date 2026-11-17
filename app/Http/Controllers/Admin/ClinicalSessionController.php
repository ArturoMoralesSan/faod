<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClinicalSessionRequest;
use App\Models\AcademicGroup;
use App\Models\AcademicPeriod;
use App\Models\Clinic;
use App\Models\ClinicalSession;
use App\Models\ClinicSupervisor;
use App\Models\Space;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Sesiones clínicas: prácticas supervisadas por fecha, consultorio y grupo.
 */
class ClinicalSessionController extends Controller
{
    public function index(Request $request): Response
    {
        $sessions = ClinicalSession::query()
            ->with([
                'clinic:id,name',
                'space:id,code,name',
                'group:id,code',
                'subject:id,code,name',
                'supervisor.people:id,first_name,paternal_surname,maternal_surname',
            ])
            ->withCount('students')
            ->when($request->search, fn ($q, $search) => $q->where('topic', 'like', "%{$search}%"))
            ->when($request->clinic_id, fn ($q, $id) => $q->where('clinic_id', $id))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->date_from, fn ($q, $date) => $q->whereDate('date', '>=', $date))
            ->when($request->date_to, fn ($q, $date) => $q->whereDate('date', '<=', $date))
            ->orderByDesc('date')
            ->orderBy('start_time')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/clinical-sessions/Index', [
            'sessions' => $sessions,
            'filters' => $request->only('search', 'clinic_id', 'status', 'date_from', 'date_to'),
            'clinics' => Clinic::orderBy('name')->get(['id', 'name']),
            'statuses' => ClinicalSession::STATUSES,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('admin/clinical-sessions/Create', [
            ...$this->formData(),
            'defaults' => $request->only('clinic_id', 'space_id', 'date'),
        ]);
    }

    public function store(ClinicalSessionRequest $request): RedirectResponse
    {
        $session = DB::transaction(function () use ($request) {
            $session = ClinicalSession::create([
                ...$request->validated(),
                'created_by' => $request->user()->id,
            ]);

            $this->attachGroupStudents($session);

            return $session;
        });

        return redirect()
            ->route('admin.clinical-sessions.show', $session)
            ->with('success', 'Sesión clínica programada correctamente.');
    }

    /**
     * Sesión con lista de alumnos (asistencia) e incidencias reportadas.
     */
    public function show(ClinicalSession $clinicalSession): Response
    {
        $clinicalSession->load([
            'clinic:id,name',
            'space:id,code,name',
            'academicPeriod:id,name',
            'group:id,code',
            'subject:id,code,name',
            'supervisor.people:id,first_name,paternal_surname,maternal_surname',
            'creator:id,name',
            'students' => fn ($q) => $q
                ->with('people:id,first_name,paternal_surname,maternal_surname')
                ->select('student_profiles.id', 'people_id', 'registration_number'),
            'incidents' => fn ($q) => $q->latest('occurred_at')
                ->select('id', 'clinical_session_id', 'title', 'type', 'severity', 'status', 'occurred_at'),
        ]);

        return Inertia::render('admin/clinical-sessions/Show', [
            'session' => $clinicalSession,
            'attendanceOptions' => ClinicalSession::ATTENDANCE,
            'statuses' => ClinicalSession::STATUSES,
            // Alumnos activos que todavía no están en la sesión.
            'availableStudents' => StudentProfile::query()
                ->whereHas('enrollments', fn ($q) => $q->where('status', 'activo'))
                ->whereNotIn('id', $clinicalSession->students->pluck('id'))
                ->with('people:id,first_name,paternal_surname,maternal_surname')
                ->get(['id', 'people_id', 'registration_number']),
        ]);
    }

    public function edit(ClinicalSession $clinicalSession): Response
    {
        return Inertia::render('admin/clinical-sessions/Edit', [
            'session' => $clinicalSession,
            ...$this->formData(),
        ]);
    }

    public function update(ClinicalSessionRequest $request, ClinicalSession $clinicalSession): RedirectResponse
    {
        DB::transaction(function () use ($request, $clinicalSession) {
            $groupChanged = $clinicalSession->academic_group_id !== $request->integer('academic_group_id');

            $clinicalSession->update($request->validated());

            // Al asignar o cambiar el grupo se agregan sus alumnos (sin quitar a los que ya están).
            if ($groupChanged) {
                $this->attachGroupStudents($clinicalSession);
            }
        });

        return redirect()
            ->route('admin.clinical-sessions.show', $clinicalSession)
            ->with('success', 'Sesión clínica actualizada correctamente.');
    }

    public function destroy(ClinicalSession $clinicalSession): RedirectResponse
    {
        $clinicalSession->delete();

        return redirect()
            ->route('admin.clinical-sessions.index')
            ->with('success', 'Sesión clínica eliminada correctamente.');
    }

    /**
     * Guarda la lista de alumnos con su asistencia (agrega, actualiza y quita).
     */
    public function attendance(Request $request, ClinicalSession $clinicalSession): RedirectResponse
    {
        $data = $request->validate([
            'students' => ['present', 'array'],
            'students.*.student_profile_id' => ['required', 'distinct', Rule::exists('student_profiles', 'id')->whereNull('deleted_at')],
            'students.*.attendance' => ['required', Rule::in(ClinicalSession::ATTENDANCE)],
            'students.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $clinicalSession->students()->sync(
            collect($data['students'])->mapWithKeys(fn ($row) => [
                $row['student_profile_id'] => [
                    'attendance' => $row['attendance'],
                    'notes' => $row['notes'] ?? null,
                ],
            ])->all()
        );

        return back()->with('success', 'Asistencia guardada correctamente.');
    }

    private function attachGroupStudents(ClinicalSession $session): void
    {
        if (! $session->academic_group_id) {
            return;
        }

        $studentIds = AcademicGroup::find($session->academic_group_id)
            ?->students()
            ->pluck('student_profiles.id')
            ->all() ?? [];

        $session->students()->syncWithoutDetaching($studentIds);
    }

    private function formData(): array
    {
        return [
            'clinics' => Clinic::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'spaces' => Space::whereNotNull('clinic_id')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'clinic_id', 'code', 'name']),
            'periods' => AcademicPeriod::orderByDesc('starts_at')->get(['id', 'name', 'is_current']),
            'groups' => AcademicGroup::orderBy('code')->get(['id', 'academic_period_id', 'code']),
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'teachers' => TeacherProfile::where('status', 'active')
                ->with('people:id,first_name,paternal_surname,maternal_surname')
                ->get(['id', 'people_id']),
            // Supervisores vigentes por clínica, para sugerirlos primero.
            'clinicSupervisors' => ClinicSupervisor::current()->get(['clinic_id', 'teacher_profile_id']),
            'statuses' => ClinicalSession::STATUSES,
        ];
    }
}
