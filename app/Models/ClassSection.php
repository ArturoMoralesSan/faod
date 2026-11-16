<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Materia impartida a un grupo por un docente.
 */
class ClassSection extends Model
{
    protected $fillable = [
        'academic_group_id',
        'subject_id',
        'teacher_profile_id',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(AcademicGroup::class, 'academic_group_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class)
            ->orderBy('day_of_week')
            ->orderBy('start_time');
    }
}
