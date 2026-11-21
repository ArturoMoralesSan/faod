<?php

namespace App\Reports\Definitions;

use App\Models\Clinic;
use App\Models\ClinicIncident;
use App\Reports\Column;
use App\Reports\Report;
use App\Support\Labels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class IncidentsReport extends Report
{
    public function key(): string
    {
        return 'incidencias';
    }

    public function title(): string
    {
        return 'Incidencias';
    }

    public function description(): string
    {
        return 'Fallas y eventos reportados en las clínicas, con su atención y tiempo de resolución.';
    }

    public function section(): string
    {
        return 'Clínicas';
    }

    public function permission(): string
    {
        return 'clinic-incidents.view';
    }

    public function icon(): string
    {
        return 'TriangleAlert';
    }

    public function filters(): array
    {
        return [
            self::period('Fecha del evento'),
            self::select('clinic_id', 'Clínica', self::records(Clinic::orderBy('name')->get(['id', 'name'])), 'Todas las clínicas'),
            self::select('type', 'Tipo', Labels::options(ClinicIncident::TYPES), 'Todos los tipos'),
            self::select('severity', 'Gravedad', Labels::options(ClinicIncident::SEVERITIES), 'Toda gravedad'),
            self::select('status', 'Estado', Labels::options(ClinicIncident::STATUSES), 'Todos'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('occurred_at', 'Fecha')->datetime(),
            Column::make('title', 'Incidencia'),
            Column::make('type', 'Tipo')->status(),
            Column::make('severity', 'Gravedad')->status(),
            Column::make('place', 'Clínica / equipo'),
            Column::make('reporter', 'Reportó')->muted(),
            Column::make('resolved_at', 'Resuelta')->datetime(),
            Column::make('status', 'Estado')->status(),
        ];
    }

    public function query(array $filters): Builder
    {
        return $this->wherePeriod(ClinicIncident::query(), 'occurred_at', $filters)
            ->with(['clinic:id,name', 'space:id,name', 'equipment:id,name', 'reporter:id,name'])
            ->when($filters['clinic_id'] ?? null, fn ($q, $id) => $q->where('clinic_id', $id))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['severity'] ?? null, fn ($q, $severity) => $q->where('severity', $severity))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('occurred_at');
    }

    public function row(Model $record): array
    {
        return [
            'occurred_at' => $record->occurred_at,
            'title' => $record->title,
            'type' => $record->type,
            'severity' => $record->severity,
            'place' => collect([$record->clinic?->name, $record->space?->name, $record->equipment?->name])->filter()->implode(' · '),
            'reporter' => $record->reporter?->name,
            'resolved_at' => $record->resolved_at,
            'status' => $record->status,
        ];
    }

    public function summary(Builder $query, array $filters): array
    {
        $byStatus = self::countBy($query, 'status');
        $bySeverity = self::countBy($query, 'severity');

        $open = ($byStatus['abierta'] ?? 0) + ($byStatus['en_proceso'] ?? 0);

        // Horas promedio entre el evento y su resolución.
        $hours = (clone $query)->reorder()->whereNotNull('resolved_at')->get(['occurred_at', 'resolved_at'])
            ->map(fn ($incident) => $incident->occurred_at->diffInMinutes($incident->resolved_at) / 60)
            ->avg();

        return [
            ['label' => 'Incidencias', 'value' => Column::formatNumber(array_sum($byStatus))],
            ['label' => 'Sin resolver', 'value' => Column::formatNumber($open), 'tone' => $open ? 'warning' : 'positive'],
            ['label' => 'Altas o críticas', 'value' => Column::formatNumber(($bySeverity['alta'] ?? 0) + ($bySeverity['critica'] ?? 0)), 'tone' => 'negative'],
            ['label' => 'Resolución promedio', 'value' => $hours === null ? '—' : ($hours >= 48 ? Column::formatNumber(round($hours / 24, 1)).' días' : Column::formatNumber(round($hours, 1)).' h')],
        ];
    }
}
