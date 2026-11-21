<?php

namespace App\Reports\Definitions;

use App\Models\InventoryItem;
use App\Models\InventoryLot;
use App\Models\Warehouse;
use App\Reports\Column;
use App\Reports\Report;
use App\Support\Labels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StockReport extends Report
{
    public function key(): string
    {
        return 'existencias';
    }

    public function title(): string
    {
        return 'Existencias';
    }

    public function description(): string
    {
        return 'Existencia por artículo, contra el mínimo, con próxima caducidad y valor.';
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
        return 'Package';
    }

    public function filters(): array
    {
        $categories = InventoryItem::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category')
            ->map(fn ($category) => ['value' => $category, 'label' => $category])->all();

        return [
            self::select('warehouse_id', 'Almacén', self::records(Warehouse::orderBy('name')->get(['id', 'name'])), 'Todos los almacenes'),
            self::select('category', 'Categoría', $categories, 'Todas las categorías'),
            self::select('kind', 'Tipo', Labels::options(InventoryItem::KINDS), 'Material, instrumental y equipo'),
            self::select('level', 'Nivel', Labels::options(['bajo_minimo', 'sin_existencia', 'suficiente']), 'Cualquier nivel'),
            self::search('Clave o artículo...'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('code', 'Clave'),
            Column::make('name', 'Artículo'),
            Column::make('category', 'Categoría')->muted(),
            Column::make('stock', 'Existencia')->number(),
            Column::make('minimum_stock', 'Mínimo')->number(),
            Column::make('unit', 'Unidad')->muted(),
            Column::make('next_expiry', 'Próxima caducidad')->date(),
            Column::make('value', 'Valor')->money(),
            Column::make('level', 'Nivel')->status(),
        ];
    }

    public function query(array $filters): Builder
    {
        $warehouse = $filters['warehouse_id'] ?? null;
        $lots = fn () => InventoryLot::query()
            ->whereColumn('inventory_lots.inventory_item_id', 'inventory_items.id')
            ->when($warehouse, fn ($q) => $q->where('warehouse_id', $warehouse));

        $stock = '(select coalesce(sum(quantity), 0) from inventory_lots where inventory_lots.inventory_item_id = inventory_items.id'
            .($warehouse ? ' and inventory_lots.warehouse_id = '.(int) $warehouse : '').')';

        return InventoryItem::query()
            ->where('is_active', true)
            ->select('inventory_items.*')
            ->selectSub($lots()->selectRaw('coalesce(sum(quantity), 0)'), 'stock')
            ->selectSub($lots()->selectRaw('coalesce(sum(quantity * coalesce(unit_cost, 0)), 0)'), 'value')
            ->selectSub($lots()->where('quantity', '>', 0)->selectRaw('min(expires_at)'), 'next_expiry')
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->when($filters['kind'] ?? null, fn ($q, $kind) => $q->where('kind', $kind))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")))
            ->when($filters['level'] ?? null, fn ($q, $level) => match ($level) {
                'sin_existencia' => $q->whereRaw("{$stock} <= 0"),
                'bajo_minimo' => $q->whereRaw("{$stock} > 0")->whereRaw("{$stock} < inventory_items.minimum_stock"),
                default => $q->whereRaw("{$stock} >= inventory_items.minimum_stock")->whereRaw("{$stock} > 0"),
            })
            ->orderBy('name');
    }

    public static function level(float $stock, float $minimum): string
    {
        return match (true) {
            $stock <= 0 => 'sin_existencia',
            $stock < $minimum => 'bajo_minimo',
            default => 'suficiente',
        };
    }

    public function row(Model $record): array
    {
        return [
            'code' => $record->code,
            'name' => $record->name,
            'category' => $record->category,
            'stock' => (float) $record->stock,
            'minimum_stock' => (float) $record->minimum_stock,
            'unit' => $record->unit,
            'next_expiry' => $record->next_expiry,
            'value' => (float) $record->value,
            'level' => self::level((float) $record->stock, (float) $record->minimum_stock),
        ];
    }

    public function summary(Builder $query, array $filters): array
    {
        $items = (clone $query)->reorder()->get();
        $levels = $items->countBy(fn ($item) => self::level((float) $item->stock, (float) $item->minimum_stock));
        $value = $items->sum(fn ($item) => (float) $item->value);

        return [
            ['label' => 'Artículos', 'value' => Column::formatNumber($levels->sum())],
            ['label' => 'Bajo mínimo', 'value' => Column::formatNumber($levels['bajo_minimo'] ?? 0), 'tone' => 'warning'],
            ['label' => 'Sin existencia', 'value' => Column::formatNumber($levels['sin_existencia'] ?? 0), 'tone' => 'negative'],
            ['label' => 'Valor del inventario', 'value' => self::money($value)],
        ];
    }
}
