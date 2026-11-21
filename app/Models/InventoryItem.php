<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Artículo de inventario (material, instrumental, consumible).
 */
class InventoryItem extends Model
{
    use LogsActivity, SoftDeletes;

    /** Material e insumos (se consumen), instrumental (se reutiliza) o equipo. */
    public const KINDS = ['material', 'instrumental', 'equipo'];

    protected $fillable = [
        'code',
        'name',
        'category',
        'kind',
        'unit',
        'minimum_stock',
        'tracks_expiration',
        'default_supplier_id',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'minimum_stock' => 'float',
            // Existencia calculada con withStock() (MySQL la regresa como texto).
            'stock' => 'float',
            'tracks_expiration' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function defaultSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'default_supplier_id');
    }

    public function lots(): HasMany
    {
        return $this->hasMany(InventoryLot::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class)->latest('moved_at')->latest('id');
    }

    /** Agrega stock (existencia total) a la consulta. */
    public function scopeWithStock(Builder $query, ?int $warehouseId = null): Builder
    {
        return $query->withSum(
            ['lots as stock' => fn ($q) => $q->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))],
            'quantity'
        );
    }

    /** Artículos activos con existencia total por debajo del mínimo. */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('minimum_stock', '>', 0)
            ->whereRaw(
                '(select coalesce(sum(quantity), 0) from inventory_lots where inventory_lots.inventory_item_id = inventory_items.id) < inventory_items.minimum_stock'
            );
    }
}
