<?php

namespace App\Reports\Definitions;

use App\Models\AcademicActivity;
use App\Models\ActivityParticipant;
use App\Reports\Column;
use App\Reports\Report;
use App\Support\Labels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ActivityParticipantsReport extends Report
{
    public function key(): string
    {
        return 'participantes';
    }

    public function title(): string
    {
        return 'Participantes de actividades';
    }

    public function description(): string
    {
        return 'Inscritos a cursos, congresos y talleres con asistencia, calificación, pago y constancia.';
    }

    public function section(): string
    {
        return 'Académico';
    }

    public function permission(): string
    {
        return 'activities.view';
    }

    public function icon(): string
    {
        return 'Presentation';
    }

    public function filters(): array
    {
        $activities = AcademicActivity::orderByDesc('starts_at')->get(['id', 'title'])
            ->map(fn ($activity) => ['value' => $activity->id, 'label' => $activity->title])->all();

        return [
            self::select('academic_activity_id', 'Actividad', $activities, 'Todas las actividades'),
            self::select('status', 'Estado', Labels::options(ActivityParticipant::STATUSES), 'Todos'),
            self::select('payment_status', 'Pago', Labels::options(ActivityParticipant::PAYMENT_STATUSES), 'Cualquier pago'),
            self::search('Nombre del participante...'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('activity', 'Actividad'),
            Column::make('name', 'Participante'),
            Column::make('profile', 'Perfil')->status(),
            Column::make('attendance', 'Asistencia')->percent(),
            Column::make('grade', 'Calificación')->number(),
            Column::make('payment_status', 'Pago')->status(),
            Column::make('certificate_folio', 'Constancia')->muted(),
            Column::make('status', 'Estado')->status(),
        ];
    }

    public function query(array $filters): Builder
    {
        return ActivityParticipant::query()
            ->with([
                'activity:id,title,type,starts_at',
                'people:id,first_name,middle_name,paternal_surname,maternal_surname',
                'people.studentProfile:id,people_id',
                'people.teacherProfile:id,people_id',
            ])
            ->when($filters['academic_activity_id'] ?? null, fn ($q, $id) => $q->where('academic_activity_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['payment_status'] ?? null, fn ($q, $status) => $q->where('payment_status', $status))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->whereHas('people', fn ($q) => $q->search($search)))
            ->orderBy('academic_activity_id')
            ->orderBy('id');
    }

    public function row(Model $record): array
    {
        $people = $record->people;

        return [
            'activity' => $record->activity?->title,
            'name' => $people?->full_name,
            'profile' => match (true) {
                (bool) $people?->studentProfile => 'alumno',
                (bool) $people?->teacherProfile => 'docente',
                default => 'externo',
            },
            'attendance' => $record->attendance,
            'grade' => $record->grade,
            'payment_status' => $record->payment_status,
            'certificate_folio' => $record->certificate_folio,
            'status' => $record->status,
        ];
    }

    public function summary(Builder $query, array $filters): array
    {
        $byStatus = self::countBy($query, 'status');
        $byPayment = self::countBy($query, 'payment_status');

        return [
            ['label' => 'Participantes', 'value' => Column::formatNumber(array_sum($byStatus))],
            ['label' => 'Acreditados', 'value' => Column::formatNumber($byStatus['acreditado'] ?? 0), 'tone' => 'positive'],
            ['label' => 'Constancias emitidas', 'value' => Column::formatNumber((clone $query)->reorder()->whereNotNull('certificate_folio')->count())],
            ['label' => 'Pagos pendientes', 'value' => Column::formatNumber($byPayment['pendiente'] ?? 0), 'tone' => 'warning'],
        ];
    }
}
