<?php

namespace App\Models;

use App\Models\Concerns\Reviewable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Diagnóstico presuntivo o definitivo de un paciente.
 */
class PatientDiagnosis extends Model
{
    use Reviewable, SoftDeletes;

    public const TYPES = ['presuntivo', 'definitivo'];

    public const REVIEW_STATUSES = ['pendiente', 'autorizado', 'correcciones', 'rechazado'];

    protected $fillable = [
        'patient_id',
        'patient_consultation_id',
        'diagnosis_id',
        'description',
        'tooth',
        'surfaces',
        'type',
        'review_status',
        'diagnosed_at',
        'student_profile_id',
        'teacher_profile_id',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'diagnosed_at' => 'date:Y-m-d',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(PatientConsultation::class, 'patient_consultation_id');
    }

    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class);
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

    public function reviewTitle(): string
    {
        $name = $this->diagnosis ? "{$this->diagnosis->code} {$this->diagnosis->name}" : $this->description;

        return trim(($this->tooth ? "Diente {$this->tooth} · " : '').$name);
    }

    public function reviewUrl(): string
    {
        return route('admin.patients.show', $this->patient_id).'#diagnosticos';
    }
}
