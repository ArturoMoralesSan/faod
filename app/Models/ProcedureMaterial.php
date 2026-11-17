<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Material utilizado en un procedimiento.
 */
class ProcedureMaterial extends Model
{
    protected $fillable = ['treatment_procedure_id', 'inventory_item_id', 'warehouse_id', 'name', 'quantity', 'unit'];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
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

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(TreatmentProcedure::class, 'treatment_procedure_id');
    }
}
