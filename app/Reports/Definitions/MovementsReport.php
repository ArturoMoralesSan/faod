<?php

namespace App\Reports\Definitions;

use App\Models\InventoryMovement;
use App\Models\Warehouse;
use App\Reports\Column;
use App\Reports\Report;
use App\Support\Labels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MovementsReport extends Report
{
    public function key(): string
    {
        return 'movimientos';
    }

    public function title(): string
    {
        return 'Movimientos de inventario';
    }

    public function description(): string
    {
        return 'Kárdex del periodo: entradas, salidas, entregas a alumnos, devoluciones, mermas, dañados, ajustes, traspasos y consumo clínico.';
    }

    public function section(): string
    {
        return 'Inventario';
    }

    public function permission(): string
    {
        return 'inventory.view';
    }

    public function icon(): string
    {
        return 'Boxes';
    }

    public function filters(): array
    {
        return [
            self::period('Fecha del movimiento'),
            self::select('warehouse_id', 'Almacén', self::records(Warehouse::orderBy('name')->get(['id', 'name'])), 'Todos los almacenes'),
            self::select('type', 'Tipo', Labels::options(InventoryMovement::TYPES), 'Todos los tipos'),
            self::search('Clave o artículo...'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('moved_at', 'Fecha')->datetime(),
            Column::make('type', 'Tipo')->status(),
            Column::make('item', 'Artículo'),
            Column::make('warehouse', 'Almacén'),
            Column::make('lot', 'Lote')->muted(),
            Column::make('quantity', 'Cantidad')->number(),
            Column::make('balance_after', 'Saldo')->number(),
            Column::make('detail', 'Motivo / referencia')->muted(),
            Column::make('user', 'Usuario')->muted(),
        ];
    }

    public function query(array $filters): Builder
    {
        return $this->wherePeriod(InventoryMovement::query(), 'moved_at', $filters)
            ->with(['item:id,code,name,unit', 'warehouse:id,name', 'lot:id,lot_number', 'user:id,name', 'supplier:id,name', 'student:id,people_id,registration_number', 'student.people:id,first_name,paternal_surname,maternal_surname'])
            ->when($filters['warehouse_id'] ?? null, fn ($q, $id) => $q->where('warehouse_id', $id))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->whereHas('item', fn ($q) => $q
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")))
            ->orderBy('moved_at')
            ->orderBy('id');
    }

    public function row(Model $record): array
    {
        return [
            'moved_at' => $record->moved_at,
            'type' => $record->type,
            'item' => collect([$record->item?->code, $record->item?->name])->filter()->implode(' · '),
            'warehouse' => $record->warehouse?->name,
            'lot' => $record->lot?->lot_number,
            'quantity' => (float) $record->quantity,
            'balance_after' => (float) $record->balance_after,
            'detail' => collect([$record->student ? "Alumno {$record->student->registration_number} {$record->student->people?->full_name}" : null, $record->reason, $record->reference, $record->supplier?->name])->filter()->implode(' · '),
            'user' => $record->user?->name,
        ];
    }

    public function summary(Builder $query, array $filters): array
    {
        $byType = self::countBy($query, 'type');
        $base = (clone $query)->reorder();

        return [
            ['label' => 'Movimientos', 'value' => Column::formatNumber(array_sum($byType))],
            ['label' => 'Unidades que entraron', 'value' => Column::formatNumber((float) (clone $base)->where('quantity', '>', 0)->sum('quantity')), 'tone' => 'positive'],
            ['label' => 'Unidades que salieron', 'value' => Column::formatNumber(abs((float) (clone $base)->where('quantity', '<', 0)->sum('quantity'))), 'tone' => 'negative'],
            ['label' => 'Consumo clínico', 'value' => Column::formatNumber($byType['consumo'] ?? 0).' mov.'],
            ['label' => 'Mermas y dañados', 'value' => Column::formatNumber(($byType['merma'] ?? 0) + ($byType['danado'] ?? 0)).' mov.', 'tone' => 'warning'],
        ];
    }
}
