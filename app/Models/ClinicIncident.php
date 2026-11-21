<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Incidencia en una clínica: falla de equipo, instalaciones, bioseguridad, etc.
 */
class ClinicIncident extends Model
{
    use LogsActivity, HasAttachments, SoftDeletes;

    public const TYPES = ['equipo', 'instalaciones', 'bioseguridad', 'seguridad', 'paciente', 'otro'];

    public const SEVERITIES = ['baja', 'media', 'alta', 'critica'];

    public const STATUSES = ['abierta', 'en_proceso', 'resuelta', 'cerrada'];

    /** Estados que cierran la incidencia y requieren resolución. */
    public const CLOSED_STATUSES = ['resuelta', 'cerrada'];

    protected $fillable = [
        'clinic_id',
        'space_id',
        'clinic_equipment_id',
        'clinical_session_id',
        'type',
        'severity',
        'status',
        'title',
        'description',
        'occurred_at',
        'reported_by',
        'resolution',
        'resolved_at',
        'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'resolved_at' => 'datetime',
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

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(ClinicEquipment::class, 'clinic_equipment_id');
    }

    public function clinicalSession(): BelongsTo
    {
        return $this->belongsTo(ClinicalSession::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isClosed(): bool
    {
        return in_array($this->status, self::CLOSED_STATUSES, true);
    }
}
