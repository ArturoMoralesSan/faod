<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Cita de un paciente en una clínica.
 */
class Appointment extends Model
{
    use LogsActivity, SoftDeletes;

    public const STATUSES = ['programada', 'confirmada', 'atendida', 'no_asistio', 'cancelada', 'reprogramada'];

    /** Estados que ocupan el horario. */
    public const ACTIVE_STATUSES = ['programada', 'confirmada', 'atendida'];

    /** Estados que ya no se pueden modificar. */
    public const CLOSED_STATUSES = ['atendida', 'no_asistio', 'cancelada', 'reprogramada'];

    protected $fillable = [
        'patient_id',
        'clinic_id',
        'space_id',
        'student_profile_id',
        'teacher_profile_id',
        'treatment_plan_id',
        'rescheduled_from_id',
        'date',
        'start_time',
        'end_time',
        'reason',
        'status',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
        'reminder_sent_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    protected function startTime(): Attribute
    {
        return Attribute::set(fn (?string $value) => SpaceAvailability::withSeconds($value));
    }

    protected function endTime(): Attribute
    {
        return Attribute::set(fn (?string $value) => SpaceAvailability::withSeconds($value));
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_profile_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class);
    }

    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rescheduled_from_id');
    }

    public function rescheduledTo(): HasOne
    {
        return $this->hasOne(self::class, 'rescheduled_from_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isClosed(): bool
    {
        return in_array($this->status, self::CLOSED_STATUSES, true);
    }

    /** Citas vigentes de la fecha que se traslapan con el rango dado. */
    public function scopeOverlapping(Builder $query, string $date, string $startTime, string $endTime): Builder
    {
        return $query
            ->whereDate('date', $date)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime);
    }
}
