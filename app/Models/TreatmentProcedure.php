<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\Reviewable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Procedimiento de un plan de tratamiento (planeado, realizado o cancelado).
 */
class TreatmentProcedure extends Model
{
    use LogsActivity, Reviewable;

    public const STATUSES = ['planeado', 'realizado', 'cancelado'];

    protected $fillable = [
        'treatment_plan_id',
        'treatment_stage_id',
        'patient_id',
        'procedure_id',
        'patient_diagnosis_id',
        'description',
        'tooth',
        'surfaces',
        'status',
        'review_status',
        'cost',
        'performed_at',
        'student_profile_id',
        'teacher_profile_id',
        'notes',
        'patient_instructions',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'float',
            'performed_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class, 'treatment_plan_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(TreatmentStage::class, 'treatment_stage_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(PatientDiagnosis::class, 'patient_diagnosis_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_profile_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ProcedureMaterial::class);
    }

    public function reviewTitle(): string
    {
        $name = $this->procedure ? "{$this->procedure->code} {$this->procedure->name}" : $this->description;

        return trim(($this->tooth ? "Diente {$this->tooth} · " : '').$name);
    }

    public function reviewUrl(): string
    {
        return route('admin.treatment-plans.show', $this->treatment_plan_id);
    }
}
