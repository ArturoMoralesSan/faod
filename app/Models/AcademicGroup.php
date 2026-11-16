<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class AcademicGroup extends Model
{
    protected $fillable = [
        'cohort_id',
        'academic_period_id',
        'period_number',
        'code',
        'capacity',
    ];

    protected function casts(): array
    {
        return [
            'period_number' => 'integer',
            'capacity' => 'integer',
        ];
    }

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(StudentProfile::class, 'academic_group_student')
            ->withTimestamps();
    }

    public function classSections(): HasMany
    {
        return $this->hasMany(ClassSection::class);
    }

    public function schedules(): HasManyThrough
    {
        return $this->hasManyThrough(ClassSchedule::class, ClassSection::class);
    }
}
