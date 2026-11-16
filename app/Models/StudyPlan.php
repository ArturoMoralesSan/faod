<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyPlan extends Model
{
    use HasAttachments;

    protected $fillable = [
        'academic_program_id',
        'name',
        'version',
        'valid_from',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function academicProgram(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(AcademicModule::class)->orderBy('sort');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class)
            ->withPivot(['id', 'academic_module_id', 'period_number', 'is_required', 'sort'])
            ->withTimestamps()
            ->orderByPivot('period_number')
            ->orderByPivot('sort');
    }
}
