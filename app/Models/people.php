<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class People extends Model
{
    use SoftDeletes;

    protected $table = 'people';

    protected $fillable = [
        'user_id',
        'curp',
        'rfc',
        'first_name',
        'middle_name',
        'paternal_surname',
        'maternal_surname',
        'birth_date',
        'gender',
        'phone',
        'personal_email',
        'street',
        'exterior_number',
        'interior_number',
        'neighborhood',
        'postal_code',
        'city',
        'state',
        'country',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class, 'people_id');
    }

    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class, 'people_id');
    }

    public function staffProfile(): HasOne
    {
        return $this->hasOne(StaffProfile::class, 'people_id');
    }

    public function getFullNameAttribute(): string
    {
        return collect([
            $this->first_name,
            $this->middle_name,
            $this->paternal_surname,
            $this->maternal_surname,
        ])->filter()->implode(' ');
    }
}
