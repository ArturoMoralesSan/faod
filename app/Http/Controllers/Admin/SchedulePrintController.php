<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicGroup;
use App\Models\AcademicPeriod;
use App\Models\ClassSchedule;
use App\Models\Setting;
use App\Models\Space;
use App\Models\TeacherProfile;
use App\Support\PdfRenderer;
use App\Support\TeacherScope;
use App\Support\WeeklyScheduleGrid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Horario semanal imprimible (PDF) de un grupo, un docente o un espacio.
 */
class SchedulePrintController extends Controller
{
    private const RELATIONS = [
        'space:id,code,name',
        'classSection.subject:id,code,name',
        'classSection.group:id,code',
        'classSection.teacher.people:id,first_name,paternal_surname,maternal_surname',
    ];

    public function group(Request $request, AcademicGroup $group): Response
    {
        // El alumno llega desde "Mi horario", que ya valida que sea su grupo.
        if (! $request->routeIs('admin.my-schedule.*')) {
            TeacherScope::ensureVisible($group, $request->user());
        }

        $group->load(['cohort.academicProgram:id,name', 'academicPeriod:id,name']);

        $schedules = $this->schedules(
            fn (Builder $q) => $q->whereHas('classSection', fn ($q) => $q->where('academic_group_id', $group->id))
        );

        return $this->render($request, [
            'title' => "Horario del grupo {$group->code}",
            'subtitle' => collect([
                $group->cohort?->academicProgram?->name,
                $group->cohort ? "Generación {$group->cohort->name}" : null,
                "Semestre {$group->period_number}",
            ])->filter()->implode(' · '),
            'period' => $group->academicPeriod?->name,
            'secondaryLabel' => 'Docente',
            'filename' => "horario-grupo-{$group->code}",
        ], $schedules, fn (ClassSchedule $s) => [
            $this->teacherName($s),
            $s->space?->code,
        ], fn (ClassSchedule $s) => $this->teacherName($s) ?? 'Sin docente');
    }

    public function teacher(Request $request, TeacherProfile $teacher): Response
    {
        $period = $this->period($request);
        $teacher->load('people');

        $schedules = $this->schedules(fn (Builder $q) => $q
            ->whereHas('classSection', fn ($q) => $q->where('teacher_profile_id', $teacher->id))
            ->whereHas('classSection.group', fn ($q) => $q->where('academic_period_id', $period?->id)));

        return $this->render($request, [
            'title' => 'Horario de '.$teacher->people->full_name,
            'subtitle' => 'Clases que imparte el docente',
            'period' => $period?->name,
            'secondaryLabel' => 'Grupo',
            'filename' => 'horario-docente-'.$teacher->people->full_name,
        ], $schedules, fn (ClassSchedule $s) => [
            'Grupo '.$s->classSection->group->code,
            $s->space?->code,
        ], fn (ClassSchedule $s) => 'Grupo '.$s->classSection->group->code);
    }

    public function space(Request $request, Space $space): Response
    {
        $period = $this->period($request);

        $schedules = $this->schedules(fn (Builder $q) => $q
            ->where('space_id', $space->id)
            ->whereHas('classSection.group', fn ($q) => $q->where('academic_period_id', $period?->id)));

        return $this->render($request, [
            'title' => "Ocupación de {$space->code} · {$space->name}",
            'subtitle' => 'Clases programadas en el espacio',
            'period' => $period?->name,
            'secondaryLabel' => 'Grupo y docente',
            'filename' => "horario-espacio-{$space->code}",
        ], $schedules, fn (ClassSchedule $s) => [
            'Grupo '.$s->classSection->group->code,
            $this->teacherName($s),
        ], fn (ClassSchedule $s) => collect(['Grupo '.$s->classSection->group->code, $this->teacherName($s)])->filter()->implode(' · '));
    }

    /** Periodo pedido en la URL o, si no, el vigente. */
    private function period(Request $request): ?AcademicPeriod
    {
        return $request->integer('academic_period_id')
            ? AcademicPeriod::find($request->integer('academic_period_id'))
            : AcademicPeriod::current();
    }

    /** @return Collection<int, ClassSchedule> */
    private function schedules(callable $scope): Collection
    {
        return ClassSchedule::query()
            ->tap($scope)
            ->with(self::RELATIONS)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }

    private function teacherName(ClassSchedule $schedule): ?string
    {
        return $schedule->classSection->teacher?->people?->full_name;
    }

    /**
     * @param  callable(ClassSchedule): array  $lines  renglones bajo la materia en cada bloque
     * @param  callable(ClassSchedule): string  $secondary  segunda columna del resumen
     */
    private function render(Request $request, array $meta, Collection $schedules, callable $lines, callable $secondary): Response
    {
        // Un color por materia, en el orden en que aparecen.
        $colors = $schedules->pluck('classSection.subject_id')->unique()->values()->flip();

        $grid = new WeeklyScheduleGrid($schedules->map(fn (ClassSchedule $s) => [
            'day' => $s->day_of_week,
            'start' => $s->start_time,
            'end' => $s->end_time,
            'title' => $s->classSection->subject->name,
            'lines' => array_values(array_filter($lines($s))),
            'color' => $colors[$s->classSection->subject_id],
        ])->all());

        // Resumen por materia (y grupo o docente): bloques y horas a la semana.
        $summary = $schedules
            ->groupBy(fn (ClassSchedule $s) => $s->class_section_id)
            ->map(function (Collection $items) use ($secondary, $colors) {
                $first = $items->first();
                $minutes = $items->sum(fn ($s) => WeeklyScheduleGrid::minutes(substr($s->start_time, 0, 5), substr($s->end_time, 0, 5)));

                return [
                    'subject' => trim(($first->classSection->subject->code ? $first->classSection->subject->code.' · ' : '').$first->classSection->subject->name),
                    'secondary' => $secondary($first),
                    'when' => $items->map(fn ($s) => mb_substr(ClassSchedule::DAYS[$s->day_of_week], 0, 3).' '
                        .substr($s->start_time, 0, 5).'–'.substr($s->end_time, 0, 5)
                        .($s->space ? " ({$s->space->code})" : ''))->implode(', '),
                    'hours' => $minutes / 60,
                    'color' => $colors[$first->classSection->subject_id],
                ];
            })
            ->sortBy('subject')
            ->values();

        $html = view('schedules.pdf', [
            ...$meta,
            'grid' => $grid,
            'summary' => $summary,
            'totalHours' => $summary->sum('hours'),
            'institution' => Setting::get('institution.name', 'Facultad de Odontología'),
            'university' => Setting::get('institution.university', ''),
            'generatedBy' => $request->user()->name,
            'generatedAt' => now(),
        ])->render();

        $filename = Str::slug($meta['filename'].' '.($meta['period'] ?? '')).'.pdf';

        return PdfRenderer::render($html, $filename, landscape: true);
    }
}
