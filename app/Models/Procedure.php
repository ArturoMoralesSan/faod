<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo de procedimientos odontológicos.
 */
class Procedure extends Model
{
    protected $fillable = ['code', 'name', 'category', 'default_cost', 'is_active'];

    protected function casts(): array
    {
        return [
            'default_cost' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function treatmentProcedures(): HasMany
    {
        return $this->hasMany(TreatmentProcedure::class);
    }
}
