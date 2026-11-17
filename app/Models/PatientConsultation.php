<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Consulta: motivo, signos vitales y exploración.
 */
class PatientConsultation extends Model
{
    protected $fillable = [
        'patient_id',
        'clinic_id',
        'clinical_session_id',
        'student_profile_id',
        'teacher_profile_id',
        'attended_at',
        'reason',
        'blood_pressure',
        'heart_rate',
        'respiratory_rate',
        'temperature',
        'weight',
        'height',
        'findings',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'attended_at' => 'datetime',
            'heart_rate' => 'integer',
            'respiratory_rate' => 'integer',
            'temperature' => 'float',
            'weight' => 'float',
            'height' => 'float',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function clinicalSession(): BelongsTo
    {
        return $this->belongsTo(ClinicalSession::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_profile_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    public function progressNotes(): HasMany
    {
        return $this->hasMany(PatientProgressNote::class);
    }
}
