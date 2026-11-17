<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Equipo de una clínica (unidad dental, autoclave, rayos X, etc.).
 */
class ClinicEquipment extends Model
{
    use SoftDeletes;

    public const STATUSES = ['operativo', 'mantenimiento', 'fuera_servicio', 'baja'];

    protected $table = 'clinic_equipment';

    protected $fillable = [
        'clinic_id',
        'space_id',
        'code',
        'name',
        'brand',
        'model',
        'serial_number',
        'status',
        'acquired_at',
        'last_maintenance_at',
        'next_maintenance_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'acquired_at' => 'date',
            'last_maintenance_at' => 'date',
            'next_maintenance_at' => 'date',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(ClinicIncident::class);
    }
}
