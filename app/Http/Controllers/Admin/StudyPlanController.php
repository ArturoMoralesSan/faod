<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StudyPlanRequest;
use App\Models\AcademicProgram;
use App\Models\StudyPlan;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Planes académicos internos.
 */
class StudyPlanController extends Controller
{
    public function index(Request $request): Response
    {
        $plans = StudyPlan::query()
            ->with('academicProgram:id,name,code')
            ->withCount('subjects')
            ->when($request->academic_program_id, fn ($q, $id) => $q->where('academic_program_id', $id))
            ->latest('valid_from')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/study-plans/Index', [
            'plans' => $plans,
            'filters' => $request->only('academic_program_id'),
            'programs' => AcademicProgram::orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/study-plans/Create', [
            'programs' => AcademicProgram::orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function store(StudyPlanRequest $request): RedirectResponse
    {
        $plan = StudyPlan::create($request->validated());

        return redirect()
            ->route('admin.study-plans.show', $plan)
            ->with('success', 'Plan registrado correctamente.');
    }

    /**
     * Vista del plan organizada por semestre/periodo, con sus módulos.
     */
    public function show(StudyPlan $studyPlan): Response
    {
        $studyPlan->load(['academicProgram:id,name,code,total_periods', 'modules', 'subjects', 'attachments']);

        return Inertia::render('admin/study-plans/Show', [
            'plan' => $studyPlan,
            'subjectsByPeriod' => $studyPlan->subjects->groupBy('pivot.period_number'),
            'availableSubjects' => Subject::where('is_active', true)
                ->whereNotIn('id', $studyPlan->subjects->pluck('id'))
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function edit(StudyPlan $studyPlan): Response
    {
        return Inertia::render('admin/study-plans/Edit', [
            'plan' => $studyPlan,
            'programs' => AcademicProgram::orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function update(StudyPlanRequest $request, StudyPlan $studyPlan): RedirectResponse
    {
        $studyPlan->update($request->validated());

        return redirect()
            ->route('admin.study-plans.show', $studyPlan)
            ->with('success', 'Plan actualizado correctamente.');
    }

    public function destroy(StudyPlan $studyPlan): RedirectResponse
    {
        $studyPlan->delete();

        return redirect()
            ->route('admin.study-plans.index')
            ->with('success', 'Plan eliminado correctamente.');
    }
}
