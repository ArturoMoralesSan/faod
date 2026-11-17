<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Existencia de un artículo en un almacén (por lote si tiene caducidad).
 */
class InventoryLot extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'warehouse_id',
        'supplier_id',
        'lot_number',
        'expires_at',
        'quantity',
        'unit_cost',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'date:Y-m-d',
            'received_at' => 'date:Y-m-d',
            'quantity' => 'float',
            'unit_cost' => 'float',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** Lotes con existencia que caducan dentro de los días indicados (o ya caducaron). */
    public function scopeExpiringWithin(Builder $query, int $days): Builder
    {
        return $query
            ->where('quantity', '>', 0)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', today()->addDays($days));
    }
}
