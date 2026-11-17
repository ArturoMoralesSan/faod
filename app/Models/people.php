<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class People extends Model
{
    use HasAttachments, SoftDeletes;

    protected $table = 'people';

    protected $fillable = [
        'user_id',
        'curp',
        'rfc',
        'first_name',
        'middle_name',
        'paternal_surname',
        'maternal_surname',
        'photo_path',
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

    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class, 'people_id');
    }

    /**
     * Búsqueda por nombre (cada palabra en cualquiera de los nombres o apellidos), CURP o teléfono.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        $search = trim($search);

        return $query->where(function ($q) use ($search) {
            $q->where('curp', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere(function ($q) use ($search) {
                    foreach (preg_split('/\s+/', $search) as $word) {
                        $q->where(fn ($q) => $q
                            ->where('first_name', 'like', "%{$word}%")
                            ->orWhere('middle_name', 'like', "%{$word}%")
                            ->orWhere('paternal_surname', 'like', "%{$word}%")
                            ->orWhere('maternal_surname', 'like', "%{$word}%"));
                    }
                });
        });
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
