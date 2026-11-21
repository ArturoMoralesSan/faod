<?php

namespace App\Models;

use App\Support\Labels;
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

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(ClinicIncident::class);
    }

    public function academicActivities(): HasMany
    {
        return $this->hasMany(AcademicActivity::class);
    }

    /**
     * Historial de uso: sesiones clínicas, citas, actividades e incidencias en el rango,
     * en un solo listado del más reciente al más antiguo.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function usageHistory(string $from, string $to, int $limit = 60)
    {
        $person = 'people:id,first_name,paternal_surname,maternal_surname';

        $sessions = $this->clinicalSessions()
            ->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)
            ->with(['subject:id,name', "supervisor.{$person}"])
            ->withCount('students')
            ->get()
            ->map(fn ($s) => [
                'kind' => 'sesion',
                'date' => $s->date?->toDateString(),
                'time' => substr((string) $s->start_time, 0, 5).'–'.substr((string) $s->end_time, 0, 5),
                'title' => $s->subject?->name ?? $s->topic ?? 'Sesión clínica',
                'detail' => collect([$s->supervisor?->people?->full_name, "{$s->students_count} alumno(s)"])->filter()->implode(' · '),
                'status' => $s->status,
            ]);

        $appointments = $this->appointments()
            ->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)
            ->with(["patient.{$person}", "student.{$person}"])
            ->get()
            ->map(fn ($a) => [
                'kind' => 'cita',
                'date' => $a->date?->toDateString(),
                'time' => substr((string) $a->start_time, 0, 5).'–'.substr((string) $a->end_time, 0, 5),
                'title' => $a->patient?->people?->full_name ?? 'Cita',
                'detail' => collect([$a->student?->people?->full_name, $a->reason])->filter()->implode(' · '),
                'status' => $a->status,
            ]);

        $activities = $this->academicActivities()
            ->whereDate('starts_at', '<=', $to)
            ->where(fn ($q) => $q->whereDate('ends_at', '>=', $from)->orWhere(fn ($q) => $q->whereNull('ends_at')->whereDate('starts_at', '>=', $from)))
            ->get(['id', 'title', 'type', 'starts_at', 'ends_at', 'schedule', 'status'])
            ->map(fn ($a) => [
                'kind' => 'actividad',
                'date' => $a->starts_at?->toDateString(),
                'time' => $a->schedule,
                'title' => $a->title,
                'detail' => Labels::get($a->type),
                'status' => $a->status,
            ]);

        $incidents = $this->incidents()
            ->whereDate('occurred_at', '>=', $from)->whereDate('occurred_at', '<=', $to)
            ->get(['id', 'title', 'severity', 'status', 'occurred_at'])
            ->map(fn ($i) => [
                'kind' => 'incidencia',
                'date' => $i->occurred_at?->toDateString(),
                'time' => $i->occurred_at?->format('H:i'),
                'title' => $i->title,
                'detail' => 'Gravedad '.mb_strtolower(Labels::get($i->severity)),
                'status' => $i->status,
            ]);

        return $sessions->concat($appointments)->concat($activities)->concat($incidents)
            ->sortByDesc(fn ($row) => ($row['date'] ?? '').' '.($row['time'] ?? ''))
            ->take($limit)
            ->values();
    }
}
