<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentStatusChange extends Model
{
    protected $fillable = [
        'student_enrollment_id',
        'from_status',
        'to_status',
        'reason',
        'changed_at',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'date',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
