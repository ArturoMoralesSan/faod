<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Área de una clínica (Endodoncia, Rayos X, Esterilización, etc.).
 */
class ClinicArea extends Model
{
    protected $fillable = [
        'clinic_id',
        'name',
        'description',
        'is_active',
    ];

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

    public function spaces(): HasMany
    {
        return $this->hasMany(Space::class);
    }
}
