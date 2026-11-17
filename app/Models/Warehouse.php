<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Almacén general o de una clínica.
 */
class Warehouse extends Model
{
    protected $fillable = ['clinic_id', 'code', 'name', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(InventoryLot::class);
    }
}
