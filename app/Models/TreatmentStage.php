<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Etapa o fase de un plan de tratamiento (higiénica, restaurativa, protésica…).
 */
class TreatmentStage extends Model
{
    protected $fillable = ['treatment_plan_id', 'order', 'name', 'description'];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class, 'treatment_plan_id');
    }

    public function procedures(): HasMany
    {
        return $this->hasMany(TreatmentProcedure::class);
    }
}
