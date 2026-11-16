<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Specialty extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function academicPrograms(): HasMany
    {
        return $this->hasMany(AcademicProgram::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(TeacherProfile::class)->withTimestamps();
    }
}
