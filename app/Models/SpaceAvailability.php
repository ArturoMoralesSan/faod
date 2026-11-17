<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Horario en que un consultorio está disponible para atención clínica.
 */
class SpaceAvailability extends Model
{
    protected $fillable = [
        'space_id',
        'academic_period_id',
        'day_of_week',
        'start_time',
        'end_time',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }

    /** Guarda las horas como HH:MM:SS para compararlas igual en cualquier motor. */
    protected function startTime(): Attribute
    {
        return Attribute::set(fn (?string $value) => self::withSeconds($value));
    }

    protected function endTime(): Attribute
    {
        return Attribute::set(fn (?string $value) => self::withSeconds($value));
    }

    /**
     * ¿El consultorio atiende en ese día y horario? Si no tiene horario
     * definido para el periodo, se considera disponible siempre.
     */
    public static function coversSlot(int $spaceId, ?int $periodId, string $date, string $start, string $end): bool
    {
        $availabilities = static::query()->where('space_id', $spaceId)->forPeriod($periodId);

        if (! (clone $availabilities)->exists()) {
            return true;
        }

        return $availabilities
            ->where('day_of_week', Carbon::parse($date)->dayOfWeekIso)
            ->where('start_time', '<=', self::withSeconds($start))
            ->where('end_time', '>=', self::withSeconds($end))
            ->exists();
    }

    public static function withSeconds(?string $time): ?string
    {
        return $time !== null && strlen($time) === 5 ? "{$time}:00" : $time;
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    /** Horarios que aplican al periodo dado (los que no tienen periodo aplican siempre). */
    public function scopeForPeriod(Builder $query, ?int $academicPeriodId): Builder
    {
        return $query->where(fn ($q) => $q
            ->whereNull('academic_period_id')
            ->when($academicPeriodId, fn ($q) => $q->orWhere('academic_period_id', $academicPeriodId)));
    }
}
