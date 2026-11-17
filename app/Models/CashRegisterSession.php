<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Turno de caja (apertura con fondo y corte al cierre).
 */
class CashRegisterSession extends Model
{
    protected $fillable = [
        'cash_register_id',
        'status',
        'opening_amount',
        'opened_by',
        'opened_at',
        'expected_cash',
        'counted_cash',
        'closed_by',
        'closed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_amount' => 'float',
            'expected_cash' => 'float',
            'counted_cash' => 'float',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Resumen del turno: cobros por forma de pago, gastos en efectivo y
     * efectivo que debería haber en caja.
     */
    public function summary(): array
    {
        $byMethod = $this->payments()
            ->whereNull('cancelled_at')
            ->selectRaw('method, count(*) as count, sum(amount) as total')
            ->groupBy('method')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->method => ['count' => (int) $row->count, 'total' => (float) $row->total]]);

        $cashIn = $byMethod['efectivo']['total'] ?? 0.0;
        $cashOut = (float) $this->expenses()->where('method', 'efectivo')->sum('amount');

        return [
            'by_method' => $byMethod,
            'total_collected' => (float) $byMethod->sum('total'),
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'cancelled' => $this->payments()->whereNotNull('cancelled_at')->count(),
            'expected_cash' => round($this->opening_amount + $cashIn - $cashOut, 2),
        ];
    }
}
