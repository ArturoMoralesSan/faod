<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Docente responsable o supervisor de una clínica (opcionalmente de un área).
 */
class ClinicSupervisor extends Model
{
    public const ROLES = ['responsable', 'supervisor'];

    protected $fillable = [
        'clinic_id',
        'clinic_area_id',
        'teacher_profile_id',
        'role',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
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

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    /** Asignaciones vigentes a la fecha de hoy. */
    public function scopeCurrent(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $today))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $today));
    }
}
