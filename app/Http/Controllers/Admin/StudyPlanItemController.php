<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicModule;
use App\Models\StudyPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Módulos y asignaturas de un plan (se administran desde la vista del plan).
 */
class StudyPlanItemController extends Controller
{
    /* ------------------------------ Módulos ------------------------------ */

    public function storeModule(Request $request, StudyPlan $studyPlan): RedirectResponse
    {
        $studyPlan->modules()->create($this->validateModule($request));

        return back()->with('success', 'Módulo agregado correctamente.');
    }

    public function updateModule(Request $request, AcademicModule $module): RedirectResponse
    {
        $module->update($this->validateModule($request));

        return back()->with('success', 'Módulo actualizado correctamente.');
    }

    public function destroyModule(AcademicModule $module): RedirectResponse
    {
        $module->delete();

        return back()->with('success', 'Módulo eliminado correctamente.');
    }

    /* ---------------------------- Asignaturas ---------------------------- */

    public function attachSubject(Request $request, StudyPlan $studyPlan): RedirectResponse
    {
        $data = $this->validateSubject($request, $studyPlan, requireSubject: true);

        $studyPlan->subjects()->attach($data['subject_id'], collect($data)->except('subject_id')->all());

        return back()->with('success', 'Asignatura agregada al plan.');
    }

    public function updateSubject(Request $request, StudyPlan $studyPlan, int $subject): RedirectResponse
    {
        $data = $this->validateSubject($request, $studyPlan, requireSubject: false);

        $studyPlan->subjects()->updateExistingPivot($subject, $data);

        return back()->with('success', 'Asignatura actualizada.');
    }

    public function detachSubject(StudyPlan $studyPlan, int $subject): RedirectResponse
    {
        $studyPlan->subjects()->detach($subject);

        return back()->with('success', 'Asignatura quitada del plan.');
    }

    private function validateModule(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'period_number' => ['nullable', 'integer', 'min:1', 'max:30'],
            'description' => ['nullable', 'string'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function validateSubject(Request $request, StudyPlan $plan, bool $requireSubject): array
    {
        return $request->validate([
            'subject_id' => $requireSubject
                ? ['required', 'exists:subjects,id', Rule::unique('study_plan_subject')->where('study_plan_id', $plan->id)]
                : ['prohibited'],
            'academic_module_id' => [
                'nullable',
                Rule::exists('academic_modules', 'id')->where('study_plan_id', $plan->id),
            ],
            'period_number' => ['required', 'integer', 'min:1', 'max:30'],
            'is_required' => ['boolean'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
