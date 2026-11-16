<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherDegree extends Model
{
    public const LEVELS = ['licenciatura', 'especialidad', 'maestria', 'doctorado', 'posdoctorado'];

    protected $fillable = [
        'teacher_profile_id',
        'level',
        'title',
        'institution',
        'license_number',
        'obtained_at',
    ];

    protected function casts(): array
    {
        return [
            'obtained_at' => 'date',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }
}
