<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Pago recibido (con folio consecutivo) aplicado a uno o varios adeudos.
 */
class Payment extends Model
{
    public const METHODS = ['efectivo', 'tarjeta', 'transferencia', 'deposito'];

    protected $fillable = [
        'folio',
        'payer_type',
        'payer_id',
        'cash_register_session_id',
        'method',
        'amount',
        'reference',
        'paid_at',
        'notes',
        'received_by',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function payer(): MorphTo
    {
        return $this->morphTo();
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashRegisterSession::class, 'cash_register_session_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
