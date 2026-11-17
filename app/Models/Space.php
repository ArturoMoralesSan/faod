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
        'clinic_area_id',
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

    public function area(): BelongsTo
    {
        return $this->belongsTo(ClinicArea::class, 'clinic_area_id');
    }

    public function classSchedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(SpaceAvailability::class)
            ->orderBy('day_of_week')
            ->orderBy('start_time');
    }

    public function clinicalSessions(): HasMany
    {
        return $this->hasMany(ClinicalSession::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(ClinicEquipment::class);
    }
}
