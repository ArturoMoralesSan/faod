<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicProgram extends Model
{
    use HasAttachments, SoftDeletes;

    public const TYPES = ['especialidad', 'maestria', 'doctorado', 'diplomado'];

    public const DURATION_UNITS = ['semestres', 'cuatrimestres', 'anios', 'meses', 'semanas'];

    public const TEACHER_ROLES = ['coordinador', 'docente', 'tutor'];

    protected $fillable = [
        'department_id',
        'specialty_id',
        'type',
        'code',
        'name',
        'description',
        'duration_value',
        'duration_unit',
        'total_periods',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'duration_value' => 'integer',
            'total_periods' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function objectives(): HasMany
    {
        return $this->hasMany(ProgramObjective::class)->orderBy('sort');
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(TeacherProfile::class, 'academic_program_teacher')
            ->withPivot(['id', 'role', 'starts_at', 'ends_at'])
            ->withTimestamps();
    }

    public function coordinators(): BelongsToMany
    {
        return $this->teachers()->wherePivot('role', 'coordinador');
    }

    public function cohorts(): HasMany
    {
        return $this->hasMany(Cohort::class)->orderByDesc('starts_at');
    }

    public function studyPlans(): HasMany
    {
        return $this->hasMany(StudyPlan::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }
}
