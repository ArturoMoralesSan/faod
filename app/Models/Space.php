<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Aula, laboratorio, consultorio o auditorio.
 */
class Space extends Model
{
    use SoftDeletes;

    public const TYPES = ['aula', 'laboratorio', 'consultorio', 'auditorio', 'otro'];

    protected $fillable = [
        'clinic_id',
        'type',
        'code',
        'name',
        'building',
        'floor',
        'capacity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function classSchedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }
}
