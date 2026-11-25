<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AcademicGroupRequest;
use App\Models\AcademicGroup;
use App\Models\AcademicPeriod;
use App\Models\ClassSchedule;
use App\Models\Cohort;
use App\Models\Space;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Support\TeacherScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Grupos y sus horarios.
 */
class AcademicGroupController extends Controller
{
    public function index(Request $request): Response
    {
        $periodId = $request->integer('academic_period_id') ?: AcademicPeriod::current()?->id;

        $groups = AcademicGroup::query()
            ->tap(fn ($q) => TeacherScope::apply($q, TeacherScope::teacherId($request->user())))
            ->with(['cohort.academicProgram:id,name,code', 'academicPeriod:id,name'])
            ->withCount(['students', 'classSections'])
            ->when($periodId, fn ($q) => $q->where('academic_period_id', $periodId))
            ->when($request->cohort_id, fn ($q, $id) => $q->where('cohort_id', $id))
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/groups/Index', [
            'groups' => $groups,
            'filters' => [...$request->only('cohort_id'), 'academic_period_id' => $periodId],
            ...$this->formData(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/groups/Create', $this->formData());
    }

    public function store(AcademicGroupRequest $request): RedirectResponse
    {
        $group = DB::transaction(function () use ($request) {
            $data = $request->validated();

            $group = AcademicGroup::create(Arr::except($data, 'student_ids'));
            $group->students()->sync($data['student_ids'] ?? []);

            return $group;
        });

        return redirect()
            ->route('admin.groups.show', $group)
            ->with('success', 'Grupo registrado correctamente.');
    }

    /**
     * Grupo con alumnos, materias y horario semanal.
     */
    public function show(AcademicGroup $group): Response
    {
        TeacherScope::ensureVisible($group, request()->user());

        $group->load([
            'cohort.academicProgram:id,name,code',
            'academicPeriod:id,name',
            'students.people:id,first_name,paternal_surname,maternal_surname',
            'classSections.subject:id,code,name',
            'classSections.teacher.people:id,first_name,paternal_surname,maternal_surname',
            'classSections.schedules.space:id,code,name',
        ]);

        // Materias del plan de estudios de la generación que corresponden al semestre del grupo.
        $planSubjects = DB::table('study_plan_subject')
            ->join('student_enrollments', 'student_enrollments.study_plan_id', '=', 'study_plan_subject.study_plan_id')
            ->join('subjects', 'subjects.id', '=', 'study_plan_subject.subject_id')
            ->where('student_enrollments.cohort_id', $group->cohort_id)
            ->where('study_plan_subject.period_number', $group->period_number)
            ->whereNull('subjects.deleted_at')
            ->distinct()
            ->get(['subjects.id', 'subjects.code', 'subjects.name']);

        // Sin plan asignado a los alumnos todavía: se ofrecen todas las asignaturas activas.
        if ($planSubjects->isEmpty()) {
            $planSubjects = Subject::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']);
        }

        return Inertia::render('admin/groups/Show', [
            'group' => $group,
            'days' => ClassSchedule::DAYS,
            'planSubjects' => $planSubjects,
            'teachers' => TeacherProfile::where('status', 'active')
                ->with('people:id,first_name,paternal_surname,maternal_surname')
                ->get(['id', 'people_id']),
            'spaces' => Space::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'type']),
        ]);
    }

    public function edit(AcademicGroup $group): Response
    {
        return Inertia::render('admin/groups/Edit', [
            'group' => $group->load('students:id'),
            ...$this->formData($group),
        ]);
    }

    public function update(AcademicGroupRequest $request, AcademicGroup $group): RedirectResponse
    {
        DB::transaction(function () use ($request, $group) {
            $data = $request->validated();

            $group->update(Arr::except($data, 'student_ids'));

            if (array_key_exists('student_ids', $data)) {
                $group->students()->sync($data['student_ids'] ?? []);
            }
        });

        return redirect()
            ->route('admin.groups.show', $group)
            ->with('success', 'Grupo actualizado correctamente.');
    }

    public function destroy(AcademicGroup $group): RedirectResponse
    {
        $group->delete();

        return redirect()
            ->route('admin.groups.index')
            ->with('success', 'Grupo eliminado correctamente.');
    }

    private function formData(?AcademicGroup $group = null): array
    {
        return [
            'periods' => AcademicPeriod::orderByDesc('starts_at')->get(['id', 'name', 'is_current']),
            'cohorts' => Cohort::with('academicProgram:id,code')
                ->where('status', '!=', 'concluida')
                ->orderByDesc('starts_at')
                ->get(['id', 'name', 'academic_program_id']),
            // Alumnos con inscripción activa (en edición, solo de la generación del grupo).
            'students' => StudentProfile::query()
                ->whereHas('enrollments', fn ($q) => $q
                    ->where('status', 'activo')
                    ->when($group, fn ($q) => $q->where('cohort_id', $group->cohort_id)))
                ->with('people:id,first_name,paternal_surname,maternal_surname')
                ->get(['id', 'people_id', 'registration_number']),
        ];
    }
}
