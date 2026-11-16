<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicGroup;
use App\Models\ClassSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Materias asignadas a un grupo (se administran desde la vista del grupo).
 */
class ClassSectionController extends Controller
{
    public function store(Request $request, AcademicGroup $group): RedirectResponse
    {
        $data = $request->validate([
            'subject_id' => [
                'required',
                'exists:subjects,id',
                Rule::unique('class_sections')->where('academic_group_id', $group->id),
            ],
            'teacher_profile_id' => ['nullable', 'exists:teacher_profiles,id'],
        ]);

        $group->classSections()->create($data);

        return back()->with('success', 'Materia asignada al grupo.');
    }

    public function update(Request $request, ClassSection $classSection): RedirectResponse
    {
        $data = $request->validate([
            'teacher_profile_id' => ['nullable', 'exists:teacher_profiles,id'],
        ]);

        $classSection->update($data);

        return back()->with('success', 'Docente asignado correctamente.');
    }

    public function destroy(ClassSection $classSection): RedirectResponse
    {
        $classSection->delete();

        return back()->with('success', 'Materia quitada del grupo.');
    }
}
