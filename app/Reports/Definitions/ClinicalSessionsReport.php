<?php

namespace App\Reports\Definitions;

use App\Models\Clinic;
use App\Models\ClinicalSession;
use App\Reports\Column;
use App\Reports\Report;
use App\Support\Labels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ClinicalSessionsReport extends Report
{
    public function key(): string
    {
        return 'sesiones-clinicas';
    }

    public function title(): string
    {
        return 'Sesiones clínicas';
    }

    public function description(): string
    {
        return 'Sesiones por clínica y supervisor, con la asistencia de los alumnos.';
    }

    public function section(): string
    {
        return 'Clínicas';
    }

    public function permission(): string
    {
        return 'clinical-sessions.view';
    }

    public function icon(): string
    {
        return 'Stethoscope';
    }

    public function filters(): array
    {
        return [
            self::period('Fecha de la sesión'),
            self::select('clinic_id', 'Clínica', self::records(Clinic::orderBy('name')->get(['id', 'name'])), 'Todas las clínicas'),
            self::select('status', 'Estado', Labels::options(ClinicalSession::STATUSES), 'Todos'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('date', 'Fecha')->date(),
            Column::make('time', 'Horario'),
            Column::make('place', 'Clínica / consultorio'),
            Column::make('subject', 'Asignatura / tema'),
            Column::make('supervisor', 'Supervisor'),
            Column::make('students', 'Alumnos')->number(true),
            Column::make('attended', 'Asistieron')->number(true),
            Column::make('attendance', 'Asistencia')->percent(),
            Column::make('status', 'Estado')->status(),
        ];
    }

    public function query(array $filters): Builder
    {
        return $this->wherePeriod(ClinicalSession::query(), 'date', $filters)
            ->with([
                'clinic:id,name',
                'space:id,name',
                'subject:id,name',
                'supervisor:id,people_id',
                'supervisor.people:id,first_name,middle_name,paternal_surname,maternal_surname',
            ])
            ->withCount([
                'students',
                'students as attended_count' => fn ($q) => $q->where('clinical_session_student.attendance', 'asistio'),
            ])
            ->when($filters['clinic_id'] ?? null, fn ($q, $id) => $q->where('clinic_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('date')
            ->orderBy('start_time');
    }

    public function row(Model $record): array
    {
        return [
            'date' => $record->date,
            'time' => substr((string) $record->start_time, 0, 5).' – '.substr((string) $record->end_time, 0, 5),
            'place' => collect([$record->clinic?->name, $record->space?->name])->filter()->implode(' · '),
            'subject' => collect([$record->subject?->name, $record->topic])->filter()->implode(' · '),
            'supervisor' => $record->supervisor?->people?->full_name,
            'students' => $record->students_count,
            'attended' => $record->attended_count,
            'attendance' => $record->students_count ? round($record->attended_count * 100 / $record->students_count, 1) : null,
            'status' => $record->status,
        ];
    }

    public function summary(Builder $query, array $filters): array
    {
        $byStatus = self::countBy($query, 'status');

        $ids = (clone $query)->reorder()->select('clinical_sessions.id');
        $attendance = DB::table('clinical_session_student')
            ->whereIn('clinical_session_id', $ids)
            ->selectRaw("count(*) as total, sum(case when attendance = 'asistio' then 1 else 0 end) as attended")
            ->first();

        return [
            ['label' => 'Sesiones', 'value' => Column::formatNumber(array_sum($byStatus))],
            ['label' => 'Concluidas', 'value' => Column::formatNumber($byStatus['concluida'] ?? 0), 'tone' => 'positive'],
            ['label' => 'Canceladas', 'value' => Column::formatNumber($byStatus['cancelada'] ?? 0), 'tone' => 'negative'],
            ['label' => 'Asistencia de alumnos', 'value' => self::percent((float) $attendance->attended, (float) $attendance->total)],
        ];
    }
}
