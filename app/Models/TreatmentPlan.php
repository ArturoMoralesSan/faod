<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\Reviewable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Plan de tratamiento de un paciente.
 */
class TreatmentPlan extends Model
{
    use LogsActivity, Reviewable, SoftDeletes;

    public const STATUSES = ['en_proceso', 'concluido', 'suspendido', 'cancelado'];

    protected $fillable = [
        'patient_id',
        'title',
        'notes',
        'patient_instructions',
        'status',
        'review_status',
        'student_profile_id',
        'teacher_profile_id',
        'started_at',
        'completed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date:Y-m-d',
            'completed_at' => 'date:Y-m-d',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_profile_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(TreatmentStage::class)->orderBy('order');
    }

    public function procedures(): HasMany
    {
        return $this->hasMany(TreatmentProcedure::class);
    }

    public function reviewTitle(): string
    {
        return "Plan: {$this->title}";
    }

    public function reviewUrl(): string
    {
        return route('admin.treatment-plans.show', $this->id);
    }
}
