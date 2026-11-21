<?php

namespace App\Reports\Definitions;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Reports\Column;
use App\Reports\Report;
use App\Support\Labels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AppointmentsReport extends Report
{
    public function key(): string
    {
        return 'citas';
    }

    public function title(): string
    {
        return 'Citas';
    }

    public function description(): string
    {
        return 'Agenda del periodo con asistencia, inasistencias y cancelaciones.';
    }

    public function section(): string
    {
        return 'Agenda';
    }

    public function permission(): string
    {
        return 'appointments.view';
    }

    public function icon(): string
    {
        return 'CalendarCheck';
    }

    public function filters(): array
    {
        return [
            self::period('Fecha de la cita'),
            self::select('clinic_id', 'Clínica', self::records(Clinic::orderBy('name')->get(['id', 'name'])), 'Todas las clínicas'),
            self::select('status', 'Estado', Labels::options(Appointment::STATUSES), 'Todos'),
            self::search('Paciente o expediente...'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('date', 'Fecha')->date(),
            Column::make('time', 'Horario'),
            Column::make('patient', 'Paciente'),
            Column::make('record_number', 'Expediente')->muted(),
            Column::make('student', 'Alumno'),
            Column::make('place', 'Clínica / consultorio'),
            Column::make('reason', 'Motivo')->muted(),
            Column::make('status', 'Estado')->status(),
        ];
    }

    public function query(array $filters): Builder
    {
        return $this->wherePeriod(Appointment::query(), 'date', $filters)
            ->with([
                'patient:id,people_id,record_number',
                'patient.people:id,first_name,middle_name,paternal_surname,maternal_surname',
                'student:id,people_id',
                'student.people:id,first_name,middle_name,paternal_surname,maternal_surname',
                'clinic:id,name',
                'space:id,name',
            ])
            ->when($filters['clinic_id'] ?? null, fn ($q, $id) => $q->where('clinic_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->whereHas('patient', fn ($q) => $q
                ->where('record_number', 'like', "%{$search}%")
                ->orWhereHas('people', fn ($q) => $q->search($search))))
            ->orderBy('date')
            ->orderBy('start_time');
    }

    public function row(Model $record): array
    {
        return [
            'date' => $record->date,
            'time' => substr((string) $record->start_time, 0, 5).' – '.substr((string) $record->end_time, 0, 5),
            'patient' => $record->patient?->people?->full_name,
            'record_number' => $record->patient?->record_number,
            'student' => $record->student?->people?->full_name,
            'place' => collect([$record->clinic?->name, $record->space?->name])->filter()->implode(' · '),
            'reason' => $record->reason,
            'status' => $record->status,
        ];
    }

    public function summary(Builder $query, array $filters): array
    {
        $byStatus = self::countBy($query, 'status');
        $attended = $byStatus['atendida'] ?? 0;
        $noShow = $byStatus['no_asistio'] ?? 0;

        return [
            ['label' => 'Citas', 'value' => Column::formatNumber(array_sum($byStatus))],
            ['label' => 'Atendidas', 'value' => Column::formatNumber($attended), 'tone' => 'positive'],
            ['label' => 'Inasistencias', 'value' => Column::formatNumber($noShow), 'tone' => 'negative'],
            ['label' => 'Canceladas', 'value' => Column::formatNumber(($byStatus['cancelada'] ?? 0) + ($byStatus['reprogramada'] ?? 0))],
            ['label' => 'Asistencia', 'value' => self::percent($attended, $attended + $noShow)],
        ];
    }
}
