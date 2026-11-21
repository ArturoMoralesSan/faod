<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Inscripción interna de un alumno a un programa y generación.
 */
class StudentEnrollment extends Model
{
    use LogsActivity, HasAttachments, SoftDeletes;

    public const STATUSES = ['activo', 'baja', 'egresado'];

    protected $fillable = [
        'student_profile_id',
        'academic_program_id',
        'cohort_id',
        'study_plan_id',
        'status',
        'started_at',
        'withdrawn_at',
        'withdrawal_reason',
        'graduated_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'withdrawn_at' => 'date',
            'graduated_at' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_profile_id');
    }

    public function academicProgram(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class);
    }

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    public function studyPlan(): BelongsTo
    {
        return $this->belongsTo(StudyPlan::class);
    }

    public function universityRegistrations(): HasMany
    {
        return $this->hasMany(UniversityRegistration::class);
    }

    public function statusChanges(): HasMany
    {
        return $this->hasMany(StudentStatusChange::class)->latest('changed_at');
    }
}
