<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Práctica clínica supervisada en una fecha, horario y consultorio.
 */
class ClinicalSession extends Model
{
    use LogsActivity, SoftDeletes;

    public const STATUSES = ['programada', 'en_curso', 'concluida', 'cancelada'];

    public const ATTENDANCE = ['pendiente', 'asistio', 'falta', 'justificada'];

    protected $fillable = [
        'clinic_id',
        'space_id',
        'academic_period_id',
        'academic_group_id',
        'subject_id',
        'supervisor_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'topic',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
        ];
    }

    /** Guarda las horas como HH:MM:SS para compararlas igual en cualquier motor. */
    protected function startTime(): Attribute
    {
        return Attribute::set(fn (?string $value) => SpaceAvailability::withSeconds($value));
    }

    protected function endTime(): Attribute
    {
        return Attribute::set(fn (?string $value) => SpaceAvailability::withSeconds($value));
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AcademicGroup::class, 'academic_group_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'supervisor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(StudentProfile::class, 'clinical_session_student')
            ->withPivot(['attendance', 'notes'])
            ->withTimestamps();
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(ClinicIncident::class);
    }

    /**
     * Sesiones no canceladas de la misma fecha que se traslapan con el rango dado.
     */
    public function scopeOverlapping(Builder $query, string $date, string $startTime, string $endTime): Builder
    {
        return $query
            ->whereDate('date', $date)
            ->where('status', '!=', 'cancelada')
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime);
    }
}
