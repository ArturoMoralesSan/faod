<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AcademicProgramRequest;
use App\Models\AcademicProgram;
use App\Models\Department;
use App\Models\Specialty;
use App\Models\TeacherProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AcademicProgramController extends Controller
{
    public function index(Request $request): Response
    {
        $programs = AcademicProgram::query()
            ->with(['specialty:id,name', 'department:id,name', 'coordinators.people:id,first_name,paternal_surname'])
            ->withCount([
                'cohorts',
                'enrollments as active_students_count' => fn ($q) => $q->where('status', 'activo'),
            ])
            ->when($request->search, fn ($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->specialty_id, fn ($q, $id) => $q->where('specialty_id', $id))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/academic-programs/Index', [
            'programs' => $programs,
            'filters' => $request->only('search', 'type', 'specialty_id', 'is_active'),
            'types' => AcademicProgram::TYPES,
            'specialties' => Specialty::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/academic-programs/Create', $this->formData());
    }

    public function store(AcademicProgramRequest $request): RedirectResponse
    {
        $program = DB::transaction(function () use ($request) {
            $data = $request->validated();

            $program = AcademicProgram::create(Arr::except($data, ['objectives', 'teachers']));

            $this->syncRelations($program, $data);

            return $program;
        });

        return redirect()
            ->route('admin.academic-programs.show', $program)
            ->with('success', 'Programa registrado correctamente.');
    }

    public function show(AcademicProgram $academicProgram): Response
    {
        $academicProgram->load([
            'specialty:id,name',
            'department:id,name',
            'objectives',
            'teachers.people:id,first_name,paternal_surname,maternal_surname',
            'studyPlans' => fn ($q) => $q->withCount('subjects'),
            'cohorts' => fn ($q) => $q->withCount([
                'enrollments as active_students_count' => fn ($q) => $q->where('status', 'activo'),
            ]),
            'attachments',
        ]);

        return Inertia::render('admin/academic-programs/Show', [
            'program' => $academicProgram,
        ]);
    }

    public function edit(AcademicProgram $academicProgram): Response
    {
        $academicProgram->load(['objectives', 'teachers']);

        return Inertia::render('admin/academic-programs/Edit', [
            'program' => $academicProgram,
            ...$this->formData(),
        ]);
    }

    public function update(AcademicProgramRequest $request, AcademicProgram $academicProgram): RedirectResponse
    {
        DB::transaction(function () use ($request, $academicProgram) {
            $data = $request->validated();

            $academicProgram->update(Arr::except($data, ['objectives', 'teachers']));

            $this->syncRelations($academicProgram, $data);
        });

        return redirect()
            ->route('admin.academic-programs.show', $academicProgram)
            ->with('success', 'Programa actualizado correctamente.');
    }

    public function destroy(AcademicProgram $academicProgram): RedirectResponse
    {
        $academicProgram->delete();

        return redirect()
            ->route('admin.academic-programs.index')
            ->with('success', 'Programa eliminado correctamente.');
    }

    private function syncRelations(AcademicProgram $program, array $data): void
    {
        if (array_key_exists('objectives', $data)) {
            $program->objectives()->delete();

            foreach (array_values($data['objectives'] ?? []) as $sort => $objective) {
                $program->objectives()->create([...$objective, 'sort' => $sort]);
            }
        }

        if (array_key_exists('teachers', $data)) {
            // La tabla pivote admite el mismo docente con distintos roles,
            // por eso se reemplaza completa en vez de usar sync().
            $program->teachers()->detach();

            foreach ($data['teachers'] ?? [] as $teacher) {
                $program->teachers()->attach($teacher['teacher_profile_id'], Arr::only($teacher, [
                    'role', 'starts_at', 'ends_at',
                ]));
            }
        }
    }

    private function formData(): array
    {
        return [
            'types' => AcademicProgram::TYPES,
            'durationUnits' => AcademicProgram::DURATION_UNITS,
            'teacherRoles' => AcademicProgram::TEACHER_ROLES,
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'specialties' => Specialty::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'teachers' => TeacherProfile::query()
                ->where('status', 'active')
                ->with('people:id,first_name,paternal_surname,maternal_surname')
                ->get(['id', 'people_id']),
        ];
    }
}
