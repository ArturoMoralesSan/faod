<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicGroup;
use App\Models\ClassSchedule;
use App\Models\ClassSection;
use App\Models\TeacherAvailability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

        $teacherId = $data['teacher_profile_id'] ?? null;

        if ($teacherId && (int) $teacherId !== $classSection->teacher_profile_id) {
            $this->ensureTeacherFits($classSection, (int) $teacherId);
        }

        $classSection->update($data);

        return back()->with('success', 'Docente asignado correctamente.');
    }

    /**
     * Al cambiar el docente de una materia que ya tiene horario, cada bloque
     * debe estar libre en su agenda y dentro de su disponibilidad.
     */
    private function ensureTeacherFits(ClassSection $classSection, int $teacherId): void
    {
        $periodId = $classSection->group->academic_period_id;
        $availability = TeacherAvailability::blocksFor($teacherId, $periodId);
        $errors = [];

        foreach ($classSection->schedules as $schedule) {
            $when = ClassSchedule::DAYS[$schedule->day_of_week].' '
                .substr($schedule->start_time, 0, 5).'–'.substr($schedule->end_time, 0, 5);

            $busy = ClassSchedule::query()
                ->overlapping($periodId, $schedule->day_of_week, $schedule->start_time, $schedule->end_time)
                ->where('class_section_id', '!=', $classSection->id)
                ->whereHas('classSection', fn ($q) => $q->where('teacher_profile_id', $teacherId))
                ->exists();

            if ($busy) {
                $errors[] = "El docente ya tiene clase el {$when}.";
            } elseif ($availability && ! TeacherAvailability::covers($availability, $schedule->day_of_week, $schedule->start_time, $schedule->end_time)) {
                $errors[] = "El {$when} está fuera de la disponibilidad del docente.";
            }
        }

        if ($errors) {
            throw ValidationException::withMessages(['teacher_profile_id' => implode(' ', $errors)]);
        }
    }

    public function destroy(ClassSection $classSection): RedirectResponse
    {
        $classSection->delete();

        return back()->with('success', 'Materia quitada del grupo.');
    }
}
