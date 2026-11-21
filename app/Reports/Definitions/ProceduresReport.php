<?php

namespace App\Reports\Definitions;

use App\Models\Review;
use App\Models\TreatmentProcedure;
use App\Reports\Column;
use App\Reports\Report;
use App\Support\Labels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Procedimientos realizados, uno por renglón.
 */
class ProceduresReport extends Report
{
    public function key(): string
    {
        return 'procedimientos';
    }

    public function title(): string
    {
        return 'Procedimientos realizados';
    }

    public function description(): string
    {
        return 'Detalle de cada procedimiento: alumno, paciente, validación docente y costo.';
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
        return 'ClipboardList';
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
            Column::make('performed_at', 'Fecha')->datetime(),
            Column::make('student', 'Alumno'),
            Column::make('registration_number', 'Matrícula')->muted(),
            Column::make('patient', 'Paciente'),
            Column::make('procedure', 'Procedimiento'),
            Column::make('tooth', 'Diente')->muted(),
            Column::make('teacher', 'Validó'),
            Column::make('review_status', 'Validación')->status(),
            Column::make('cost', 'Costo')->money(),
        ];
    }

    public function query(array $filters): Builder
    {
        return self::base($this->wherePeriod(TreatmentProcedure::query(), 'performed_at', $filters), $filters)
            ->with([
                'procedure:id,code,name',
                'patient:id,people_id,record_number',
                'patient.people:id,first_name,middle_name,paternal_surname,maternal_surname',
                'student:id,people_id,registration_number',
                'student.people:id,first_name,middle_name,paternal_surname,maternal_surname',
                'teacher:id,people_id',
                'teacher.people:id,first_name,middle_name,paternal_surname,maternal_surname',
            ])
            ->orderBy('performed_at');
    }

    /** Filtros compartidos con el reporte de productividad por alumno. */
    public static function base(Builder $query, array $filters): Builder
    {
        return $query
            ->where('treatment_procedures.status', 'realizado')
            ->whereNotNull('treatment_procedures.student_profile_id')
            ->when($filters['review_status'] ?? null, fn ($q, $status) => $q->where('treatment_procedures.review_status', $status))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->whereHas('student', fn ($q) => $q
                ->where('registration_number', 'like', "%{$search}%")
                ->orWhereHas('people', fn ($q) => $q->search($search))));
    }

    public function row(Model $record): array
    {
        return [
            'performed_at' => $record->performed_at,
            'student' => $record->student?->people?->full_name,
            'registration_number' => $record->student?->registration_number,
            'patient' => collect([$record->patient?->record_number, $record->patient?->people?->full_name])->filter()->implode(' · '),
            'procedure' => $record->procedure?->name ?? $record->description,
            'tooth' => collect([$record->tooth, $record->surfaces])->filter()->implode(' '),
            'teacher' => $record->teacher?->people?->full_name,
            'review_status' => $record->review_status,
            'cost' => $record->cost,
        ];
    }

    public function summary(Builder $query, array $filters): array
    {
        $byReview = self::countBy($query, 'review_status');
        $base = (clone $query)->reorder();

        return [
            ['label' => 'Procedimientos', 'value' => Column::formatNumber(array_sum($byReview))],
            ['label' => 'Alumnos', 'value' => Column::formatNumber((clone $base)->distinct()->count('student_profile_id'))],
            ['label' => 'Autorizados', 'value' => Column::formatNumber($byReview['autorizado'] ?? 0), 'tone' => 'positive'],
            ['label' => 'Por validar', 'value' => Column::formatNumber($byReview['pendiente'] ?? 0), 'tone' => 'warning'],
            ['label' => 'Monto', 'value' => self::money((float) (clone $base)->sum('cost'))],
        ];
    }
}
