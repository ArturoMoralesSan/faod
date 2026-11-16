<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Situación de inscripción del alumno ante la universidad en un periodo.
 */
class UniversityRegistration extends Model
{
    use HasAttachments;

    public const STATUSES = ['inscrito', 'no_inscrito', 'pendiente'];

    protected $fillable = [
        'student_enrollment_id',
        'academic_period_id',
        'status',
        'registered_at',
        'verified_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'date',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
