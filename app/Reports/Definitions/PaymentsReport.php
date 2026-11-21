<?php

namespace App\Reports\Definitions;

use App\Http\Controllers\Admin\Finance\ChargeController;
use App\Models\Charge;
use App\Models\Payment;
use App\Reports\Column;
use App\Reports\Report;
use App\Support\Labels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PaymentsReport extends Report
{
    public function key(): string
    {
        return 'pagos';
    }

    public function title(): string
    {
        return 'Pagos recibidos';
    }

    public function description(): string
    {
        return 'Cobranza del periodo por método de pago, con folios y cancelaciones.';
    }

    public function section(): string
    {
        return 'Finanzas';
    }

    public function permission(): string
    {
        return 'payments.view';
    }

    public function icon(): string
    {
        return 'Receipt';
    }

    public function filters(): array
    {
        return [
            self::period('Fecha de pago'),
            self::select('method', 'Método', Labels::options(Payment::METHODS), 'Todos los métodos'),
            self::select('payer_type', 'Pagador', Labels::options([...array_keys(Charge::PAYER_TYPES), 'externo']), 'Todos los pagadores'),
            self::select('state', 'Estado', Labels::options(['vigente', 'cancelado']), 'Vigentes y cancelados'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('folio', 'Folio'),
            Column::make('paid_at', 'Fecha')->datetime(),
            Column::make('payer', 'Pagador'),
            Column::make('payer_type', 'Tipo')->status(),
            Column::make('method', 'Método')->status(),
            Column::make('reference', 'Referencia')->muted(),
            Column::make('receiver', 'Recibió')->muted(),
            Column::make('state', 'Estado')->status(),
            Column::make('amount', 'Importe')->money(),
        ];
    }

    public function query(array $filters): Builder
    {
        return $this->wherePeriod(Payment::query(), 'paid_at', $filters)
            ->with(['payer' => ChargeController::payerWithPeople(), 'receiver:id,name'])
            ->when($filters['method'] ?? null, fn ($q, $method) => $q->where('method', $method))
            ->when($filters['payer_type'] ?? null, fn ($q, $type) => $type === 'externo'
                ? $q->whereNull('payer_type')
                : $q->where('payer_type', Charge::PAYER_TYPES[$type]))
            ->when(($filters['state'] ?? null) === 'vigente', fn ($q) => $q->whereNull('cancelled_at'))
            ->when(($filters['state'] ?? null) === 'cancelado', fn ($q) => $q->whereNotNull('cancelled_at'))
            ->orderBy('paid_at')
            ->orderBy('id');
    }

    public function row(Model $record): array
    {
        // Sin alumno ni paciente: ingreso externo (curso, donativo…) con el nombre capturado.
        $type = $record->payer_type ? Charge::payerAlias($record->payer_type) : 'externo';

        return [
            'folio' => $record->folio,
            'paid_at' => $record->paid_at,
            'payer' => $type === 'externo'
                ? collect([$record->payer_name, $record->concept])->filter()->implode(' · ')
                : collect([
                    $type === 'student' ? $record->payer?->registration_number : $record->payer?->record_number,
                    $record->payer?->people?->full_name,
                ])->filter()->implode(' · '),
            'payer_type' => $type,
            'method' => $record->method,
            'reference' => $record->reference,
            'receiver' => $record->receiver?->name,
            'state' => $record->cancelled_at ? 'cancelado' : 'vigente',
            // Un pago cancelado se muestra con su importe, pero no suma al total.
            'amount' => (float) $record->amount,
            Report::EXCLUDE_FROM_TOTALS => (bool) $record->cancelled_at,
        ];
    }

    public function summary(Builder $query, array $filters): array
    {
        $valid = (clone $query)->reorder()->whereNull('cancelled_at');
        $byMethod = (clone $valid)->toBase()->selectRaw('method, sum(amount) as total')->groupBy('method')->pluck('total', 'method');
        $total = (float) $byMethod->sum();

        return [
            ['label' => 'Total cobrado', 'value' => self::money($total), 'tone' => 'positive'],
            ['label' => 'Pagos', 'value' => Column::formatNumber((clone $valid)->count())],
            ['label' => 'Efectivo', 'value' => self::money((float) ($byMethod['efectivo'] ?? 0))],
            ['label' => 'Otros medios', 'value' => self::money($total - (float) ($byMethod['efectivo'] ?? 0))],
            ['label' => 'Cancelados', 'value' => Column::formatNumber((clone $query)->reorder()->whereNotNull('cancelled_at')->count()), 'tone' => 'negative'],
        ];
    }
}
