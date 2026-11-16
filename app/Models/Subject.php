<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'credits',
        'theory_hours',
        'practice_hours',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'credits' => 'decimal:2',
            'theory_hours' => 'integer',
            'practice_hours' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function studyPlans(): BelongsToMany
    {
        return $this->belongsToMany(StudyPlan::class)
            ->withPivot(['id', 'academic_module_id', 'period_number', 'is_required', 'sort'])
            ->withTimestamps();
    }

    public function classSections(): HasMany
    {
        return $this->hasMany(ClassSection::class);
    }
}
