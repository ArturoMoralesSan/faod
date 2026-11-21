<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entrada o salida de efectivo de la caja que no es un cobro ni un gasto
 * (p. ej. depósito para cambio, retiro para llevar al banco).
 */
class CashMovement extends Model
{
    use LogsActivity;

    public const TYPES = ['deposito', 'retiro'];

    protected $fillable = ['cash_register_session_id', 'type', 'amount', 'reason', 'user_id'];

    protected function casts(): array
    {
        return ['amount' => 'float'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashRegisterSession::class, 'cash_register_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activityLabel(): string
    {
        return ($this->type === 'retiro' ? 'Retiro' : 'Depósito').' de $'.number_format($this->amount, 2);
    }
}
