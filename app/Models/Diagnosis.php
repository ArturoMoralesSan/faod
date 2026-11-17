<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo de diagnósticos (CIE-10 u otros).
 */
class Diagnosis extends Model
{
    protected $fillable = ['code', 'name', 'category', 'description', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function patientDiagnoses(): HasMany
    {
        return $this->hasMany(PatientDiagnosis::class);
    }
}
