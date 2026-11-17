<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Concepto de cobro (colegiatura, inscripción, tratamiento, constancia…).
 */
class FeeConcept extends Model
{
    public const CATEGORIES = ['colegiatura', 'inscripcion', 'tratamiento', 'curso', 'constancia', 'otro'];

    protected $fillable = ['code', 'name', 'category', 'amount', 'is_active'];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }
}
