<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Pago recibido (con folio consecutivo) aplicado a uno o varios adeudos.
 */
class Payment extends Model
{
    use LogsActivity;

    public const METHODS = ['efectivo', 'tarjeta', 'transferencia', 'deposito'];

    protected $fillable = [
        'folio',
        'payer_type',
        'payer_id',
        // Ingresos sin alumno ni paciente (cursos, externos, donativos).
        'payer_name',
        'concept',
        'fee_concept_id',
        'activity_participant_id',
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

    public function feeConcept(): BelongsTo
    {
        return $this->belongsTo(FeeConcept::class);
    }

    /** Participante de un curso o actividad cuyo pago se registró aquí. */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(ActivityParticipant::class, 'activity_participant_id');
    }
}
