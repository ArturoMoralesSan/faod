<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    public const TYPES = ['departamento', 'coordinacion'];

    protected $fillable = [
        'parent_id',
        'type',
        'code',
        'name',
        'description',
        'head_people_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(People::class, 'head_people_id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(StaffProfile::class);
    }

    public function academicPrograms(): HasMany
    {
        return $this->hasMany(AcademicProgram::class);
    }

    public function clinics(): HasMany
    {
        return $this->hasMany(Clinic::class);
    }
}
