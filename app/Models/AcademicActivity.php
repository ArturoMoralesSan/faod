<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Curso, seminario, congreso, taller u otra actividad académica.
 */
class AcademicActivity extends Model
{
    use HasAttachments, SoftDeletes;

    public const TYPES = ['curso', 'seminario', 'congreso', 'taller', 'diplomado', 'conferencia', 'otro'];

    public const MODALITIES = ['presencial', 'en_linea', 'mixta'];

    public const STATUSES = ['planeada', 'abierta', 'en_curso', 'concluida', 'cancelada'];

    protected $fillable = [
        'type',
        'title',
        'description',
        'modality',
        'starts_at',
        'ends_at',
        'schedule',
        'space_id',
        'location',
        'capacity',
        'hours',
        'cost',
        'status',
        'coordinator_id',
        'issues_certificate',
        'min_attendance',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date:Y-m-d',
            'ends_at' => 'date:Y-m-d',
            'capacity' => 'integer',
            'hours' => 'integer',
            'cost' => 'float',
            'issues_certificate' => 'boolean',
            'min_attendance' => 'integer',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'coordinator_id');
    }

    public function instructors(): HasMany
    {
        return $this->hasMany(ActivityInstructor::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ActivityParticipant::class);
    }

    public function isFull(): bool
    {
        return $this->capacity !== null
            && $this->participants()->where('status', '!=', 'baja')->count() >= $this->capacity;
    }
}
