<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clinic extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'department_id',
        'code',
        'name',
        'location',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function spaces(): HasMany
    {
        return $this->hasMany(Space::class);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(ClinicArea::class)->orderBy('name');
    }

    public function supervisors(): HasMany
    {
        return $this->hasMany(ClinicSupervisor::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(ClinicEquipment::class)->orderBy('name');
    }

    public function clinicalSessions(): HasMany
    {
        return $this->hasMany(ClinicalSession::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(ClinicIncident::class);
    }
}
