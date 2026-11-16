<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CohortRequest;
use App\Models\AcademicProgram;
use App\Models\Cohort;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Generaciones.
 */
class CohortController extends Controller
{
    public function index(Request $request): Response
    {
        $cohorts = Cohort::query()
            ->with('academicProgram:id,name,code')
            ->withCount([
                'groups',
                'enrollments as active_students_count' => fn ($q) => $q->where('status', 'activo'),
            ])
            ->when($request->academic_program_id, fn ($q, $id) => $q->where('academic_program_id', $id))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('starts_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/cohorts/Index', [
            'cohorts' => $cohorts,
            'filters' => $request->only('academic_program_id', 'status'),
            ...$this->formData(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/cohorts/Create', $this->formData());
    }

    public function store(CohortRequest $request): RedirectResponse
    {
        Cohort::create($request->validated());

        return redirect()
            ->route('admin.cohorts.index')
            ->with('success', 'Generación registrada correctamente.');
    }

    public function show(Cohort $cohort): Response
    {
        $cohort->load([
            'academicProgram:id,name,code',
            'groups.academicPeriod:id,name',
            'enrollments.student.people:id,first_name,paternal_surname,maternal_surname',
        ]);

        return Inertia::render('admin/cohorts/Show', [
            'cohort' => $cohort,
        ]);
    }

    public function edit(Cohort $cohort): Response
    {
        return Inertia::render('admin/cohorts/Edit', [
            'cohort' => $cohort,
            ...$this->formData(),
        ]);
    }

    public function update(CohortRequest $request, Cohort $cohort): RedirectResponse
    {
        $cohort->update($request->validated());

        return redirect()
            ->route('admin.cohorts.index')
            ->with('success', 'Generación actualizada correctamente.');
    }

    public function destroy(Cohort $cohort): RedirectResponse
    {
        $cohort->delete();

        return redirect()
            ->route('admin.cohorts.index')
            ->with('success', 'Generación eliminada correctamente.');
    }

    private function formData(): array
    {
        return [
            'statuses' => Cohort::STATUSES,
            'programs' => AcademicProgram::orderBy('name')->get(['id', 'name', 'code']),
        ];
    }
}
