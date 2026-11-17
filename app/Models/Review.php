<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Revisión docente de un registro clínico.
 */
class Review extends Model
{
    public const DECISIONS = ['autorizado', 'correcciones', 'rechazado'];

    /** Registros que se pueden revisar (alias usado desde el frontend). */
    public const REVIEWABLE_TYPES = [
        'patient_diagnosis' => PatientDiagnosis::class,
        'treatment_plan' => TreatmentPlan::class,
        'treatment_procedure' => TreatmentProcedure::class,
    ];

    protected $fillable = [
        'reviewable_type',
        'reviewable_id',
        'reviewer_id',
        'teacher_profile_id',
        'decision',
        'comments',
    ];

    protected static function booted(): void
    {
        // El estado de revisión del registro es la última decisión.
        static::created(function (Review $review) {
            $review->reviewable?->forceFill(['review_status' => $review->decision])->save();
        });
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }
}
