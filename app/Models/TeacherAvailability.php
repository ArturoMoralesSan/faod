<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAvailability extends Model
{
    protected $fillable = [
        'teacher_profile_id',
        'academic_period_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    /** Disponibilidad que aplica al periodo dado (la que no tiene periodo aplica siempre). */
    public function scopeForPeriod(Builder $query, ?int $academicPeriodId): Builder
    {
        return $query->where(fn ($q) => $q
            ->whereNull('academic_period_id')
            ->when($academicPeriodId, fn ($q) => $q->orWhere('academic_period_id', $academicPeriodId)));
    }

    /**
     * Bloques de disponibilidad del docente que aplican al periodo, o null si no
     * registró ninguno (en ese caso se le puede programar a cualquier hora).
     *
     * @return Collection<int, self>|null
     */
    public static function blocksFor(int $teacherId, ?int $periodId): ?Collection
    {
        $blocks = static::query()
            ->where('teacher_profile_id', $teacherId)
            ->forPeriod($periodId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return $blocks->isEmpty() ? null : $blocks;
    }

    /** ¿Algún bloque cubre completo el horario dado? Las horas se comparan como HH:MM. */
    public static function covers(Collection $blocks, int $dayOfWeek, string $start, string $end): bool
    {
        return $blocks->contains(fn (self $block) => $block->day_of_week === $dayOfWeek
            && substr($block->start_time, 0, 5) <= substr($start, 0, 5)
            && substr($block->end_time, 0, 5) >= substr($end, 0, 5));
    }

    /** Texto con la disponibilidad de un día, p. ej. "08:00–12:00, 16:00–19:00". */
    public static function describeDay(Collection $blocks, int $dayOfWeek): string
    {
        $ranges = $blocks
            ->where('day_of_week', $dayOfWeek)
            ->map(fn (self $block) => substr($block->start_time, 0, 5).'–'.substr($block->end_time, 0, 5));

        return $ranges->isEmpty() ? 'sin disponibilidad' : $ranges->implode(', ');
    }
}
