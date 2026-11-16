<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentProfile extends Model
{
    use HasAttachments, SoftDeletes;

    protected $fillable = [
        'people_id',
        'registration_number',
        'status',
        'admission_date',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'admission_date' => 'date',
        ];
    }

    public function people(): BelongsTo
    {
        return $this->belongsTo(People::class, 'people_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class)->latest('started_at');
    }

    public function activeEnrollment(): HasOne
    {
        return $this->hasOne(StudentEnrollment::class)
            ->where('status', 'activo')
            ->latestOfMany('started_at');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(AcademicGroup::class, 'academic_group_student')
            ->withTimestamps();
    }
}
