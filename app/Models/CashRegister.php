<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CashRegister extends Model
{
    protected $fillable = ['clinic_id', 'name', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CashRegisterSession::class)->latest('opened_at');
    }

    public function openSession(): HasOne
    {
        return $this->hasOne(CashRegisterSession::class)->where('status', 'abierta');
    }
}
