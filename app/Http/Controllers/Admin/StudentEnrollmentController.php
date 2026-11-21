<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StudentEnrollmentRequest;
use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\Specialty;
use App\Models\StudentEnrollment;
use App\Models\StudentProfile;
use App\Models\UniversityRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Control interno de alumnos: inscripción a programa, generación,
 * bajas, egresos e inscripción ante la universidad.
 */
class StudentEnrollmentController extends Controller
{
    public function index(Request $request): Response
    {
        $periodId = $request->integer('academic_period_id') ?: AcademicPeriod::current()?->id;

        $enrollments = StudentEnrollment::query()
            ->with([
                'student:id,people_id,registration_number',
                'student.people:id,first_name,paternal_surname,maternal_surname,phone,personal_email',
                'academicProgram:id,name,code,specialty_id',
                'academicProgram.specialty:id,name',
                'cohort:id,name',
                'universityRegistrations' => fn ($q) => $q->where('academic_period_id', $periodId),
            ])
            ->when($request->search, fn ($q, $search) => $q->whereHas('student', function ($q) use ($search) {
                $q->where('registration_number', 'like', "%{$search}%")
                    ->orWhereHas('people', fn ($q) => $q
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('paternal_surname', 'like', "%{$search}%")
                        ->orWhere('maternal_surname', 'like', "%{$search}%"));
            }))
            ->when($request->academic_program_id, fn ($q, $id) => $q->where('academic_program_id', $id))
            ->when($request->specialty_id, fn ($q, $id) => $q->whereHas('academicProgram', fn ($q) => $q->where('specialty_id', $id)))
            ->when($request->cohort_id, fn ($q, $id) => $q->where('cohort_id', $id))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->university_status, function ($q, $status) use ($periodId) {
                // "pendiente" incluye a quien no tiene registro en el periodo.
                $status === 'pendiente'
                    ? $q->whereDoesntHave('universityRegistrations', fn ($q) => $q
                        ->where('academic_period_id', $periodId)
                        ->whereIn('status', ['inscrito', 'no_inscrito']))
                    : $q->whereHas('universityRegistrations', fn ($q) => $q
                        ->where('academic_period_id', $periodId)
                        ->where('status', $status));
            })
            ->latest('started_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/students/Index', [
            'enrollments' => $enrollments,
            'filters' => [
                ...$request->only('search', 'academic_program_id', 'specialty_id', 'cohort_id', 'status', 'university_status'),
                'academic_period_id' => $periodId,
            ],
            'statuses' => StudentEnrollment::STATUSES,
            'universityStatuses' => UniversityRegistration::STATUSES,
            'programs' => AcademicProgram::with('cohorts:id,academic_program_id,name')->orderBy('name')->get(['id', 'name', 'code']),
            'specialties' => Specialty::orderBy('name')->get(['id', 'name']),
            'periods' => AcademicPeriod::orderByDesc('starts_at')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('admin/students/Create', [
            ...$this->formData(),
            'studentProfileId' => $request->integer('student_profile_id') ?: null,
        ]);
    }

    public function store(StudentEnrollmentRequest $request): RedirectResponse
    {
        $enrollment = DB::transaction(function () use ($request) {
            $data = $request->validated();

            $enrollment = StudentEnrollment::create(Arr::except($data, 'status_reason'));

            $enrollment->statusChanges()->create([
                'from_status' => null,
                'to_status' => $enrollment->status,
                'reason' => $data['status_reason'] ?? 'Alta en el programa',
                'changed_at' => $enrollment->started_at ?? now(),
                'user_id' => $request->user()->id,
            ]);

            return $enrollment;
        });

        return redirect()
            ->route('admin.students.show', $enrollment)
            ->with('success', 'Alumno inscrito correctamente.');
    }

    /**
     * Historial interno del alumno en el programa.
     */
    public function show(StudentEnrollment $student): Response
    {
        $student->load([
            'student.people',
            'student.attachments',
            'student.groups.academicPeriod:id,name',
            'academicProgram.specialty:id,name',
            'cohort:id,name',
            'studyPlan:id,name,version',
            'universityRegistrations.academicPeriod:id,name',
            'universityRegistrations.attachments',
            'universityRegistrations.verifier:id,name',
            'statusChanges.user:id,name',
            'attachments',
        ]);

        return Inertia::render('admin/students/Show', [
            'enrollment' => $student,
            'universityStatuses' => UniversityRegistration::STATUSES,
            'periods' => AcademicPeriod::orderByDesc('starts_at')->get(['id', 'name']),
            'canEdit' => (bool) auth()->user()?->can('students.edit'),
        ]);
    }

    public function edit(StudentEnrollment $student): Response
    {
        return Inertia::render('admin/students/Edit', [
            'enrollment' => $student,
            ...$this->formData(),
        ]);
    }

    public function update(StudentEnrollmentRequest $request, StudentEnrollment $student): RedirectResponse
    {
        DB::transaction(function () use ($request, $student) {
            $data = $request->validated();
            $previousStatus = $student->status;

            $student->update(Arr::except($data, 'status_reason'));

            // Baja interna, reingreso o egreso: queda en el historial.
            if ($previousStatus !== $student->status) {
                $student->statusChanges()->create([
                    'from_status' => $previousStatus,
                    'to_status' => $student->status,
                    'reason' => $data['status_reason'] ?? $data['withdrawal_reason'] ?? null,
                    'changed_at' => match ($student->status) {
                        'baja' => $student->withdrawn_at ?? now(),
                        'egresado' => $student->graduated_at ?? now(),
                        default => now(),
                    },
                    'user_id' => $request->user()->id,
                ]);
            }
        });

        return redirect()
            ->route('admin.students.show', $student)
            ->with('success', 'Información actualizada correctamente.');
    }

    public function destroy(StudentEnrollment $student): RedirectResponse
    {
        $student->delete();

        return redirect()
            ->route('admin.students.index')
            ->with('success', 'Inscripción eliminada correctamente.');
    }

    private function formData(): array
    {
        return [
            'statuses' => StudentEnrollment::STATUSES,
            'programs' => AcademicProgram::query()
                ->where('is_active', true)
                ->with(['cohorts:id,academic_program_id,name', 'studyPlans:id,academic_program_id,name,version'])
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'students' => StudentProfile::with('people:id,first_name,paternal_surname,maternal_surname')
                ->get(['id', 'people_id', 'registration_number']),
        ];
    }
}
