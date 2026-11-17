<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCountLine extends Model
{
    protected $fillable = ['inventory_count_id', 'inventory_lot_id', 'system_quantity', 'counted_quantity'];

    protected function casts(): array
    {
        return [
            'system_quantity' => 'float',
            'counted_quantity' => 'float',
        ];
    }

    public function count(): BelongsTo
    {
        return $this->belongsTo(InventoryCount::class, 'inventory_count_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class, 'inventory_lot_id');
    }
}
