<?php

namespace App\Reports\Definitions;

use App\Http\Controllers\Admin\Finance\ChargeController;
use App\Models\Charge;
use App\Models\FeeConcept;
use App\Reports\Column;
use App\Reports\Report;
use App\Support\Labels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ChargesReport extends Report
{
    public function key(): string
    {
        return 'adeudos';
    }

    public function title(): string
    {
        return 'Adeudos';
    }

    public function description(): string
    {
        return 'Cargos a alumnos y pacientes con descuentos, pagos, saldo y vencimiento.';
    }

    public function section(): string
    {
        return 'Finanzas';
    }

    public function permission(): string
    {
        return 'charges.view';
    }

    public function icon(): string
    {
        return 'HandCoins';
    }

    public function filters(): array
    {
        return [
            self::period('Fecha de vencimiento', null),
            self::select('status', 'Estado', Labels::options([...Charge::STATUSES, 'vencido']), 'Todos'),
            self::select('payer_type', 'Pagador', Labels::options(array_keys(Charge::PAYER_TYPES)), 'Alumnos y pacientes'),
            self::select('fee_concept_id', 'Concepto', self::records(FeeConcept::orderBy('name')->get(['id', 'code', 'name'])), 'Todos los conceptos'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('payer', 'Pagador'),
            Column::make('payer_type', 'Tipo')->status(),
            Column::make('description', 'Concepto'),
            Column::make('due_date', 'Vence')->date(),
            Column::make('amount', 'Importe')->money(),
            Column::make('discount_amount', 'Descuento')->money(),
            Column::make('paid_amount', 'Pagado')->money(),
            Column::make('balance', 'Saldo')->money(),
            Column::make('status', 'Estado')->status(),
        ];
    }

    public function query(array $filters): Builder
    {
        return $this->wherePeriod(Charge::query(), 'due_date', $filters)
            ->with(['payer' => ChargeController::payerWithPeople()])
            ->when($filters['status'] ?? null, fn ($q, $status) => $status === 'vencido' ? $q->overdue() : $q->where('status', $status))
            ->when($filters['payer_type'] ?? null, fn ($q, $type) => $q->where('payer_type', Charge::PAYER_TYPES[$type]))
            ->when($filters['fee_concept_id'] ?? null, fn ($q, $id) => $q->where('fee_concept_id', $id))
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderBy('id');
    }

    public function row(Model $record): array
    {
        $type = Charge::payerAlias($record->payer_type);
        $overdue = in_array($record->status, ['pendiente', 'parcial'], true) && $record->due_date && $record->due_date->isBefore(today());

        return [
            'payer' => collect([
                $type === 'student' ? $record->payer?->registration_number : $record->payer?->record_number,
                $record->payer?->people?->full_name,
            ])->filter()->implode(' · '),
            'payer_type' => $type,
            'description' => $record->description,
            'due_date' => $record->due_date,
            'amount' => (float) $record->amount,
            'discount_amount' => (float) $record->discount_amount,
            'paid_amount' => (float) $record->paid_amount,
            'balance' => $record->balance,
            'status' => $overdue ? 'vencido' : $record->status,
        ];
    }

    public function summary(Builder $query, array $filters): array
    {
        $active = (clone $query)->reorder()->where('status', '!=', 'cancelado');
        $totals = (clone $active)->toBase()
            ->selectRaw('count(*) as charges, coalesce(sum(amount - discount_amount), 0) as net, coalesce(sum(paid_amount), 0) as paid')
            ->first();
        $overdue = (clone $query)->reorder()->overdue()->toBase()
            ->selectRaw('coalesce(sum(amount - discount_amount - paid_amount), 0) as balance')
            ->value('balance');

        return [
            ['label' => 'Adeudos', 'value' => Column::formatNumber((int) $totals->charges)],
            ['label' => 'Importe neto', 'value' => self::money((float) $totals->net)],
            ['label' => 'Cobrado', 'value' => self::money((float) $totals->paid), 'tone' => 'positive'],
            ['label' => 'Saldo pendiente', 'value' => self::money(max(0, (float) $totals->net - (float) $totals->paid)), 'tone' => 'warning'],
            ['label' => 'Saldo vencido', 'value' => self::money((float) $overdue), 'tone' => 'negative'],
        ];
    }
}
