<?php

namespace App\Reports\Definitions;

use App\Models\Review;
use App\Models\StudentProfile;
use App\Models\TreatmentProcedure;
use App\Reports\Column;
use App\Reports\Report;
use App\Support\Labels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Procedimientos realizados agrupados por alumno.
 */
class StudentProductivityReport extends Report
{
    public function key(): string
    {
        return 'procedimientos-por-alumno';
    }

    public function title(): string
    {
        return 'Procedimientos por alumno';
    }

    public function description(): string
    {
        return 'Productividad clínica de cada alumno: realizados, autorizados, por validar y monto.';
    }

    public function section(): string
    {
        return 'Atención clínica';
    }

    public function permission(): string
    {
        return 'treatments.view';
    }

    public function icon(): string
    {
        return 'Trophy';
    }

    public function filters(): array
    {
        return [
            self::period('Fecha de realización'),
            self::select('review_status', 'Validación', Labels::options(['pendiente', ...Review::DECISIONS]), 'Toda validación'),
            self::search('Alumno o matrícula...'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('registration_number', 'Matrícula'),
            Column::make('student', 'Alumno'),
            Column::make('total', 'Realizados')->number(true),
            Column::make('authorized', 'Autorizados')->number(true),
            Column::make('pending', 'Por validar')->number(true),
            Column::make('rejected', 'Con observaciones')->number(true),
            Column::make('patients', 'Pacientes')->number(),
            Column::make('amount', 'Monto')->money(),
        ];
    }

    public function landscape(): bool
    {
        return false;
    }

    /** Procedimientos filtrados (base de la agrupación y de los indicadores). */
    private function procedures(array $filters): Builder
    {
        return ProceduresReport::base($this->wherePeriod(TreatmentProcedure::query(), 'treatment_procedures.performed_at', $filters), $filters);
    }

    public function query(array $filters): Builder
    {
        $stats = $this->procedures($filters)
            ->toBase()
            ->select('treatment_procedures.student_profile_id')
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when review_status = 'autorizado' then 1 else 0 end) as authorized")
            ->selectRaw("sum(case when review_status = 'pendiente' then 1 else 0 end) as pending")
            ->selectRaw("sum(case when review_status in ('correcciones', 'rechazado') then 1 else 0 end) as rejected")
            ->selectRaw('count(distinct patient_id) as patients')
            ->selectRaw('coalesce(sum(cost), 0) as amount')
            ->groupBy('treatment_procedures.student_profile_id');

        return StudentProfile::query()
            ->joinSub($stats, 'stats', 'stats.student_profile_id', '=', 'student_profiles.id')
            ->select('student_profiles.id', 'student_profiles.people_id', 'student_profiles.registration_number', 'stats.*')
            ->with('people:id,first_name,middle_name,paternal_surname,maternal_surname')
            ->orderByDesc('stats.total')
            ->orderBy('student_profiles.registration_number');
    }

    public function row(Model $record): array
    {
        return [
            'registration_number' => $record->registration_number,
            'student' => $record->people?->full_name,
            'total' => (int) $record->total,
            'authorized' => (int) $record->authorized,
            'pending' => (int) $record->pending,
            'rejected' => (int) $record->rejected,
            'patients' => (int) $record->patients,
            'amount' => (float) $record->amount,
        ];
    }

    public function summary(Builder $query, array $filters): array
    {
        $procedures = $this->procedures($filters);
        $students = (clone $query)->reorder()->count();
        $total = (clone $procedures)->count();

        return [
            ['label' => 'Alumnos', 'value' => Column::formatNumber($students)],
            ['label' => 'Procedimientos', 'value' => Column::formatNumber($total)],
            ['label' => 'Promedio por alumno', 'value' => $students ? Column::formatNumber(round($total / $students, 1)) : '—'],
            ['label' => 'Autorizados', 'value' => self::percent((clone $procedures)->where('review_status', 'autorizado')->count(), $total), 'tone' => 'positive'],
            ['label' => 'Monto', 'value' => self::money((float) (clone $procedures)->sum('cost'))],
        ];
    }
}
