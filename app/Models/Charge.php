<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Adeudo de un alumno o un paciente.
 */
class Charge extends Model
{
    public const STATUSES = ['pendiente', 'parcial', 'pagado', 'cancelado'];

    /** Quién paga (alias usado desde el frontend). */
    public const PAYER_TYPES = [
        'student' => StudentProfile::class,
        'patient' => Patient::class,
    ];

    protected $fillable = [
        'payer_type',
        'payer_id',
        'fee_concept_id',
        'academic_period_id',
        'treatment_procedure_id',
        'description',
        'amount',
        'discount_amount',
        'discount_reason',
        'discount_authorized_by',
        'paid_amount',
        'due_date',
        'status',
        'cancellation_reason',
        'notes',
        'created_by',
    ];

    protected $appends = ['balance'];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'discount_amount' => 'float',
            'paid_amount' => 'float',
            'due_date' => 'date:Y-m-d',
        ];
    }

    public function payer(): MorphTo
    {
        return $this->morphTo();
    }

    public function concept(): BelongsTo
    {
        return $this->belongsTo(FeeConcept::class, 'fee_concept_id');
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function treatmentProcedure(): BelongsTo
    {
        return $this->belongsTo(TreatmentProcedure::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getBalanceAttribute(): float
    {
        return $this->status === 'cancelado'
            ? 0.0
            : round(max(0, $this->amount - $this->discount_amount - $this->paid_amount), 2);
    }

    /** Recalcula lo pagado (pagos no cancelados) y el estado. */
    public function refreshPaidStatus(): void
    {
        if ($this->status === 'cancelado') {
            return;
        }

        $paid = (float) $this->allocations()
            ->whereHas('payment', fn ($q) => $q->whereNull('cancelled_at'))
            ->sum('amount');

        $net = round($this->amount - $this->discount_amount, 2);

        $this->forceFill([
            'paid_amount' => $paid,
            'status' => $paid <= 0 ? ($net <= 0 ? 'pagado' : 'pendiente') : ($paid + 0.004 >= $net ? 'pagado' : 'parcial'),
        ])->save();
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['pendiente', 'parcial']);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()->whereDate('due_date', '<', today());
    }

    public function scopeForPayer(Builder $query, Model $payer): Builder
    {
        return $query->where('payer_type', $payer->getMorphClass())->where('payer_id', $payer->getKey());
    }

    public static function payerAlias(string $class): ?string
    {
        return array_search($class, self::PAYER_TYPES, true) ?: null;
    }
}
