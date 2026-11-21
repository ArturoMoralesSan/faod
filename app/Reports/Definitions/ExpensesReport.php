<?php

namespace App\Reports\Definitions;

use App\Models\Clinic;
use App\Models\Expense;
use App\Models\Payment;
use App\Reports\Column;
use App\Reports\Report;
use App\Support\Labels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ExpensesReport extends Report
{
    public function key(): string
    {
        return 'gastos';
    }

    public function title(): string
    {
        return 'Gastos';
    }

    public function description(): string
    {
        return 'Egresos del periodo por categoría, proveedor, clínica y laboratorio.';
    }

    public function section(): string
    {
        return 'Finanzas';
    }

    public function permission(): string
    {
        return 'expenses.view';
    }

    public function icon(): string
    {
        return 'Wallet';
    }

    public function filters(): array
    {
        return [
            self::period('Fecha del gasto'),
            self::select('category', 'Categoría', array_map(fn ($c) => ['value' => $c, 'label' => self::category($c)], Expense::CATEGORIES), 'Todas las categorías'),
            self::select('method', 'Método', Labels::options(Payment::METHODS), 'Todos los métodos'),
            self::select('clinic_id', 'Clínica', self::records(Clinic::orderBy('name')->get(['id', 'name'])), 'Todas las clínicas'),
        ];
    }

    /** "mantenimiento" en las etiquetas generales es el estado de un equipo ("En mantenimiento"). */
    private static function category(?string $category): string
    {
        return $category === 'mantenimiento' ? 'Mantenimiento' : Labels::get($category);
    }

    public function columns(): array
    {
        return [
            Column::make('paid_at', 'Fecha')->datetime(),
            Column::make('category', 'Categoría'),
            Column::make('description', 'Descripción'),
            Column::make('supplier', 'Proveedor'),
            Column::make('clinic', 'Clínica / laboratorio')->muted(),
            Column::make('method', 'Método')->status(),
            Column::make('reference', 'Referencia')->muted(),
            Column::make('amount', 'Importe')->money(),
        ];
    }

    public function query(array $filters): Builder
    {
        return $this->wherePeriod(Expense::query(), 'paid_at', $filters)
            ->with(['supplier:id,name', 'clinic:id,name', 'space:id,name'])
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->when($filters['method'] ?? null, fn ($q, $method) => $q->where('method', $method))
            ->when($filters['clinic_id'] ?? null, fn ($q, $id) => $q->where('clinic_id', $id))
            ->orderBy('paid_at')
            ->orderBy('id');
    }

    public function row(Model $record): array
    {
        return [
            'paid_at' => $record->paid_at,
            'category' => self::category($record->category),
            'description' => $record->description,
            'supplier' => $record->supplier?->name,
            'clinic' => collect([$record->clinic?->name, $record->space ? "Lab. {$record->space->name}" : null])->filter()->implode(' · '),
            'method' => $record->method,
            'reference' => $record->reference,
            'amount' => (float) $record->amount,
        ];
    }

    public function summary(Builder $query, array $filters): array
    {
        $byCategory = (clone $query)->reorder()->toBase()
            ->selectRaw('category, count(*) as expenses, sum(amount) as total')
            ->groupBy('category')
            ->get()
            ->sortByDesc('total');

        $top = $byCategory->first();

        return [
            ['label' => 'Total de gastos', 'value' => self::money((float) $byCategory->sum('total')), 'tone' => 'negative'],
            ['label' => 'Gastos', 'value' => Column::formatNumber((int) $byCategory->sum('expenses'))],
            ['label' => 'Mayor categoría', 'value' => $top ? self::category($top->category).' · '.self::money((float) $top->total) : '—'],
        ];
    }
}
