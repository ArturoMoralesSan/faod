<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicActivity;
use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\Appointment;
use App\Models\ClassSchedule;
use App\Models\ClinicalSession;
use App\Models\ClinicSupervisor;
use App\Models\KnowledgeArea;
use App\Models\Specialty;
use App\Models\TeacherDegree;
use App\Models\TeacherProfile;
use App\Models\TreatmentProcedure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestión de docentes y especialistas.
 * Los datos personales y el perfil base se editan en PeopleController.
 */
class TeacherController extends Controller
{
    public function index(Request $request): Response
    {
        $teachers = TeacherProfile::query()
            ->with([
                'people:id,first_name,paternal_surname,maternal_surname,phone,personal_email',
                'specialties:id,name',
                'academicPrograms:id,name,code',
            ])
            ->when($request->search, fn ($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('employee_number', 'like', "%{$search}%")
                    ->orWhere('professional_license', 'like', "%{$search}%")
                    ->orWhereHas('people', fn ($q) => $q
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('paternal_surname', 'like', "%{$search}%")
                        ->orWhere('maternal_surname', 'like', "%{$search}%"));
            }))
            ->when($request->specialty_id, fn ($q, $id) => $q->whereHas('specialties', fn ($q) => $q->whereKey($id)))
            ->when($request->academic_program_id, fn ($q, $id) => $q->whereHas('academicPrograms', fn ($q) => $q->whereKey($id)))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/teachers/Index', [
            'teachers' => $teachers,
            'filters' => $request->only('search', 'specialty_id', 'academic_program_id', 'status'),
            'specialties' => Specialty::orderBy('name')->get(['id', 'name']),
            'programs' => AcademicProgram::orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * Expediente del docente: formación, experiencia, disponibilidad
     * e historial de participación académica.
     */
    public function show(Request $request, TeacherProfile $teacher): Response
    {
        // El horario de clases es del periodo elegido (por omisión, el vigente).
        $periodId = $request->integer('academic_period_id') ?: AcademicPeriod::current()?->id;

        $teacher->load([
            'classSections' => fn ($q) => $q->whereHas('group', fn ($q) => $q->where('academic_period_id', $periodId)),
            'people',
            'specialties:id,name',
            'knowledgeAreas:id,name',
            'degrees',
            'certifications',
            'experiences',
            'availabilities.academicPeriod:id,name',
            'academicPrograms:id,name,code',
            'classSections.subject:id,code,name',
            'classSections.group:id,code,academic_period_id',
            'classSections.group.academicPeriod:id,name',
            'classSections.schedules.space:id,code,name',
            'attachments',
        ]);

        return Inertia::render('admin/teachers/Show', [
            'teacher' => $teacher,
            'days' => ClassSchedule::DAYS,
            'periods' => AcademicPeriod::orderByDesc('starts_at')->get(['id', 'name']),
            'academicPeriodId' => $periodId,
            'degreeLevels' => TeacherDegree::LEVELS,
            'specialties' => Specialty::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'knowledgeAreas' => KnowledgeArea::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'participation' => $this->participation($teacher),
            'canEdit' => $request->user()->can('teachers.edit'),
            'ownRecord' => $request->routeIs('admin.my-record'),
        ]);
    }

    /** Expediente del docente que inició sesión (solo lectura). */
    public function mine(Request $request): Response
    {
        $teacher = $request->user()->people?->teacherProfile;

        abort_unless($teacher, 404, 'Tu cuenta no está ligada a un expediente docente.');

        return $this->show($request, $teacher);
    }

    /** PDF del horario del docente que inició sesión. */
    public function minePdf(Request $request): \Illuminate\Http\Response
    {
        $teacher = $request->user()->people?->teacherProfile;

        abort_unless($teacher, 404);

        return app(SchedulePrintController::class)->teacher($request, $teacher);
    }

    /**
     * Historial de participación: actividades académicas (como coordinador o ponente),
     * supervisión clínica y validaciones.
     */
    private function participation(TeacherProfile $teacher): array
    {
        $activities = AcademicActivity::query()
            ->where('coordinator_id', $teacher->id)
            ->orWhereHas('instructors', fn ($q) => $q->where('teacher_profile_id', $teacher->id))
            ->with(['instructors' => fn ($q) => $q->where('teacher_profile_id', $teacher->id)])
            ->latest('starts_at')
            ->get(['id', 'title', 'type', 'starts_at', 'ends_at', 'status', 'coordinator_id'])
            ->map(fn (AcademicActivity $activity) => [
                'id' => $activity->id,
                'title' => $activity->title,
                'type' => $activity->type,
                'starts_at' => $activity->starts_at?->toDateString(),
                'status' => $activity->status,
                'role' => collect([
                    $activity->coordinator_id === $teacher->id ? 'Coordinador' : null,
                    ...$activity->instructors->pluck('role'),
                ])->filter()->unique()->implode(', '),
            ]);

        $sessions = ClinicalSession::query()->where('supervisor_id', $teacher->id);

        return [
            'activities' => $activities,
            'clinics' => ClinicSupervisor::query()
                ->where('teacher_profile_id', $teacher->id)
                ->with(['clinic:id,name', 'area:id,name'])
                ->latest('starts_at')
                ->get(['id', 'clinic_id', 'clinic_area_id', 'role', 'starts_at', 'ends_at']),
            'recentSessions' => (clone $sessions)
                ->whereDate('date', '<=', today())
                ->with(['clinic:id,name', 'subject:id,name'])
                ->withCount('students')
                ->latest('date')
                ->limit(10)
                ->get(['id', 'clinic_id', 'subject_id', 'date', 'start_time', 'end_time', 'topic', 'status']),
            'stats' => [
                'sessions' => (clone $sessions)->count(),
                'sessions_done' => (clone $sessions)->where('status', 'concluida')->count(),
                'appointments' => Appointment::where('teacher_profile_id', $teacher->id)->count(),
                'procedures_validated' => TreatmentProcedure::where('teacher_profile_id', $teacher->id)->where('review_status', 'autorizado')->count(),
                'activities' => $activities->count(),
            ],
        ];
    }

    /**
     * Sincroniza especialidades y áreas de conocimiento.
     */
    public function syncAreas(Request $request, TeacherProfile $teacher): RedirectResponse
    {
        $data = $request->validate([
            'specialty_ids' => ['present', 'array'],
            'specialty_ids.*' => ['integer', 'exists:specialties,id'],
            'knowledge_area_ids' => ['present', 'array'],
            'knowledge_area_ids.*' => ['integer', 'exists:knowledge_areas,id'],
        ]);

        DB::transaction(function () use ($teacher, $data) {
            $teacher->specialties()->sync($data['specialty_ids']);
            $teacher->knowledgeAreas()->sync($data['knowledge_area_ids']);
        });

        return back()->with('success', 'Especialidades y áreas actualizadas.');
    }
}
