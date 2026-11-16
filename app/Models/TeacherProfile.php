<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherProfile extends Model
{
    use HasAttachments, SoftDeletes;

    protected $fillable = [
        'people_id',
        'employee_number',
        'professional_license',
        'professional_title',
        'highest_degree',
        'specialty',
        'years_experience',
        'status',
        'professional_biography',
    ];

    protected function casts(): array
    {
        return [
            'years_experience' => 'integer',
        ];
    }

    public function people(): BelongsTo
    {
        return $this->belongsTo(People::class, 'people_id');
    }

    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class)->withTimestamps();
    }

    public function knowledgeAreas(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeArea::class)->withTimestamps();
    }

    public function degrees(): HasMany
    {
        return $this->hasMany(TeacherDegree::class)->latest('obtained_at');
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(TeacherCertification::class)->latest('issued_at');
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(TeacherExperience::class)->latest('started_at');
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(TeacherAvailability::class)
            ->orderBy('day_of_week')
            ->orderBy('start_time');
    }

    public function academicPrograms(): BelongsToMany
    {
        return $this->belongsToMany(AcademicProgram::class, 'academic_program_teacher')
            ->withPivot(['id', 'role', 'starts_at', 'ends_at'])
            ->withTimestamps();
    }

    public function classSections(): HasMany
    {
        return $this->hasMany(ClassSection::class);
    }
}
