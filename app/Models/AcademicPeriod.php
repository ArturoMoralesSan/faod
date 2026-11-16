<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicPeriod extends Model
{
    protected $fillable = [
        'code',
        'name',
        'starts_at',
        'ends_at',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_current' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Solo puede existir un periodo vigente.
        static::saving(function (AcademicPeriod $period) {
            if ($period->is_current) {
                static::query()
                    ->whereKeyNot($period->getKey())
                    ->where('is_current', true)
                    ->update(['is_current' => false]);
            }
        });
    }

    public static function current(): ?self
    {
        return static::where('is_current', true)->first();
    }

    public function groups(): HasMany
    {
        return $this->hasMany(AcademicGroup::class);
    }

    public function universityRegistrations(): HasMany
    {
        return $this->hasMany(UniversityRegistration::class);
    }
}
