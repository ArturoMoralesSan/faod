<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimiento del kárdex. quantity > 0 entra, quantity < 0 sale.
 */
class InventoryMovement extends Model
{
    public const TYPES = ['entrada', 'salida', 'entrega', 'devolucion', 'merma', 'danado', 'ajuste', 'traspaso', 'consumo'];

    /** Salidas que se reportan aparte: pérdidas y productos dañados. */
    public const LOSS_TYPES = ['merma', 'danado'];

    protected $fillable = [
        'inventory_item_id',
        'warehouse_id',
        'inventory_lot_id',
        'student_profile_id',
        'type',
        'quantity',
        'balance_after',
        'unit_cost',
        'reason',
        'reference',
        'supplier_id',
        'treatment_procedure_id',
        'inventory_count_id',
        'related_movement_id',
        'user_id',
        'moved_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'balance_after' => 'float',
            'unit_cost' => 'float',
            'moved_at' => 'datetime',
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

    public function lot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class, 'inventory_lot_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Alumno que recibió (entrega) o devolvió el material. */
    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_profile_id');
    }

    public function treatmentProcedure(): BelongsTo
    {
        return $this->belongsTo(TreatmentProcedure::class);
    }
}
