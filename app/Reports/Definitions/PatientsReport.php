<?php

namespace App\Reports\Definitions;

use App\Models\Clinic;
use App\Models\Patient;
use App\Reports\Column;
use App\Reports\Report;
use App\Support\Labels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PatientsReport extends Report
{
    public function key(): string
    {
        return 'pacientes';
    }

    public function title(): string
    {
        return 'Pacientes';
    }

    public function description(): string
    {
        return 'Pacientes registrados en el periodo, por clínica y estado.';
    }

    public function section(): string
    {
        return 'Atención clínica';
    }

    public function permission(): string
    {
        return 'patients.view';
    }

    public function icon(): string
    {
        return 'Contact';
    }

    public function filters(): array
    {
        return [
            self::period('Fecha de registro', null),
            self::select('clinic_id', 'Clínica', self::records(Clinic::orderBy('name')->get(['id', 'name'])), 'Todas las clínicas'),
            self::select('status', 'Estado', Labels::options(Patient::STATUSES), 'Todos'),
            self::search('Nombre, CURP o expediente...'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('record_number', 'Expediente'),
            Column::make('name', 'Nombre'),
            Column::make('gender', 'Sexo')->status(),
            Column::make('age', 'Edad')->number(),
            Column::make('phone', 'Teléfono')->muted(),
            Column::make('clinic', 'Clínica'),
            Column::make('registered_at', 'Registro')->date(),
            Column::make('status', 'Estado')->status(),
        ];
    }

    public function query(array $filters): Builder
    {
        return $this->wherePeriod(Patient::query(), 'registered_at', $filters)
            ->with([
                'people:id,first_name,middle_name,paternal_surname,maternal_surname,gender,birth_date,phone',
                'clinic:id,name',
            ])
            ->when($filters['clinic_id'] ?? null, fn ($q, $id) => $q->where('clinic_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('record_number', 'like', "%{$search}%")
                ->orWhereHas('people', fn ($q) => $q->search($search))))
            ->orderBy('record_number');
    }

    public function row(Model $record): array
    {
        $people = $record->people;

        return [
            'record_number' => $record->record_number,
            'name' => $people?->full_name,
            'gender' => $people?->gender,
            'age' => $people?->birth_date ? \Illuminate\Support\Carbon::parse($people->birth_date)->age : null,
            'phone' => $people?->phone,
            'clinic' => $record->clinic?->name,
            'registered_at' => $record->registered_at,
            'status' => $record->status,
        ];
    }

    public function summary(Builder $query, array $filters): array
    {
        $byStatus = self::countBy($query, 'status');

        return [
            ['label' => 'Pacientes', 'value' => Column::formatNumber(array_sum($byStatus))],
            ['label' => 'Activos', 'value' => Column::formatNumber($byStatus['activo'] ?? 0), 'tone' => 'positive'],
            ['label' => 'Dados de alta', 'value' => Column::formatNumber($byStatus['alta'] ?? 0)],
            ['label' => 'Bajas', 'value' => Column::formatNumber($byStatus['baja'] ?? 0), 'tone' => 'negative'],
        ];
    }
}
