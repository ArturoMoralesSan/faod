<?php

namespace App\Services;

use App\Models\CashRegisterSession;
use App\Models\Charge;
use App\Models\FeeConcept;
use App\Models\Payment;
use App\Models\StudentProfile;
use App\Models\TreatmentPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de cobranza: los pagos siempre se aplican a adeudos del mismo pagador,
 * nunca por más del saldo, y el efectivo entra a una caja abierta.
 */
class FinanceService
{
    /**
     * Registra un pago y lo aplica a los adeudos indicados ([charge_id => monto]).
     * Sin aplicación explícita, se aplica del adeudo más antiguo al más reciente.
     */
    public function registerPayment(Model $payer, array $data, User $user): Payment
    {
        $amount = round((float) $data['amount'], 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'El monto debe ser mayor que cero.']);
        }

        return DB::transaction(function () use ($payer, $data, $user, $amount) {
            $session = null;

            if ($data['method'] === 'efectivo') {
                $session = CashRegisterSession::lockForUpdate()->find($data['cash_register_session_id'] ?? null);

                if (! $session || $session->status !== 'abierta') {
                    throw ValidationException::withMessages(['cash_register_session_id' => 'Los pagos en efectivo requieren una caja abierta.']);
                }
            } elseif (! empty($data['cash_register_session_id'])) {
                $session = CashRegisterSession::where('status', 'abierta')->find($data['cash_register_session_id']);
            }

            $charges = Charge::query()
                ->forPayer($payer)
                ->open()
                ->orderByRaw('due_date is null')
                ->orderBy('due_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $allocations = $this->allocations($charges, $data['allocations'] ?? null, $amount);

            $payment = Payment::create([
                'folio' => 'TMP-'.uniqid(),
                'payer_type' => $payer->getMorphClass(),
                'payer_id' => $payer->getKey(),
                'cash_register_session_id' => $session?->id,
                'method' => $data['method'],
                'amount' => $amount,
                'reference' => $data['reference'] ?? null,
                'paid_at' => $data['paid_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'received_by' => $user->id,
            ]);

            // Folio consecutivo a partir del id (único sin bloquear la tabla).
            $payment->update(['folio' => sprintf('R-%06d', $payment->id)]);

            foreach ($allocations as $chargeId => $allocated) {
                $payment->allocations()->create(['charge_id' => $chargeId, 'amount' => $allocated]);
                $charges[$chargeId]->refreshPaidStatus();
            }

            return $payment;
        });
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Charge>  $charges
     * @return array<int, float>
     */
    private function allocations($charges, ?array $requested, float $amount): array
    {
        $result = [];

        if ($requested) {
            foreach ($requested as $chargeId => $value) {
                $value = round((float) $value, 2);

                if ($value <= 0) {
                    continue;
                }

                $charge = $charges[(int) $chargeId] ?? null;

                if (! $charge) {
                    throw ValidationException::withMessages(['allocations' => 'Uno de los adeudos no es de este pagador o ya está saldado.']);
                }

                if ($value > $charge->balance + 0.004) {
                    throw ValidationException::withMessages(['allocations' => "El monto aplicado a \"{$charge->description}\" supera su saldo."]);
                }

                $result[$charge->id] = $value;
            }

            if (abs(array_sum($result) - $amount) > 0.004) {
                throw ValidationException::withMessages(['allocations' => 'La suma aplicada a los adeudos debe ser igual al monto del pago.']);
            }

            return $result;
        }

        $remaining = $amount;

        foreach ($charges as $charge) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($remaining, $charge->balance);

            if ($take > 0) {
                $result[$charge->id] = round($take, 2);
                $remaining = round($remaining - $take, 2);
            }
        }

        if ($remaining > 0.004) {
            throw ValidationException::withMessages(['amount' => 'El pago supera el saldo pendiente del pagador.']);
        }

        return $result;
    }

    /**
     * Cancela un pago (no se elimina: queda con su folio para auditoría).
     */
    public function cancelPayment(Payment $payment, string $reason, User $user): void
    {
        if ($payment->cancelled_at) {
            throw ValidationException::withMessages(['payment' => 'El pago ya está cancelado.']);
        }

        if ($payment->session && $payment->session->status === 'cerrada') {
            throw ValidationException::withMessages(['payment' => 'El pago pertenece a un corte de caja cerrado.']);
        }

        DB::transaction(function () use ($payment, $reason, $user) {
            $payment->update([
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'cancelled_by' => $user->id,
            ]);

            $payment->allocations()->with('charge')->get()->each(fn ($allocation) => $allocation->charge->refreshPaidStatus());
        });
    }

    public function applyDiscount(Charge $charge, float $discount, string $reason, User $user): void
    {
        if ($charge->status === 'cancelado') {
            throw ValidationException::withMessages(['discount_amount' => 'El adeudo está cancelado.']);
        }

        if ($discount < 0 || $discount > $charge->amount - $charge->paid_amount + 0.004) {
            throw ValidationException::withMessages(['discount_amount' => 'El descuento no puede ser mayor que lo que falta por pagar.']);
        }

        $charge->update([
            'discount_amount' => round($discount, 2),
            'discount_reason' => $reason,
            'discount_authorized_by' => $user->id,
        ]);

        $charge->refreshPaidStatus();
    }

    /**
     * Cuota para todos los alumnos con inscripción activa (opcionalmente de una generación).
     */
    public function chargeStudents(FeeConcept $concept, array $data, User $user): int
    {
        $amount = (float) ($data['amount'] ?? $concept->amount);

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Indica el monto de la cuota.']);
        }

        return DB::transaction(function () use ($concept, $data, $user, $amount) {
            $students = StudentProfile::query()
                ->whereHas('enrollments', fn ($q) => $q
                    ->where('status', 'activo')
                    ->when($data['cohort_id'] ?? null, fn ($q, $id) => $q->where('cohort_id', $id)))
                ->get();

            $created = 0;

            foreach ($students as $student) {
                // No duplicar la misma cuota del mismo periodo.
                $exists = Charge::forPayer($student)
                    ->where('fee_concept_id', $concept->id)
                    ->where('academic_period_id', $data['academic_period_id'] ?? null)
                    ->where('status', '!=', 'cancelado')
                    ->exists();

                if ($exists) {
                    continue;
                }

                Charge::create([
                    'payer_type' => $student->getMorphClass(),
                    'payer_id' => $student->id,
                    'fee_concept_id' => $concept->id,
                    'academic_period_id' => $data['academic_period_id'] ?? null,
                    'description' => $data['description'] ?? $concept->name,
                    'amount' => $amount,
                    'due_date' => $data['due_date'] ?? null,
                    'created_by' => $user->id,
                ]);

                $created++;
            }

            return $created;
        });
    }

    /**
     * Cargos al paciente por los procedimientos realizados del plan que aún no se cobran.
     */
    public function chargeTreatmentPlan(TreatmentPlan $plan, User $user, ?string $dueDate = null): int
    {
        return DB::transaction(function () use ($plan, $user, $dueDate) {
            $procedures = $plan->procedures()
                ->with('procedure:id,code,name')
                ->where('status', 'realizado')
                ->where('cost', '>', 0)
                ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('charges')->whereColumn('charges.treatment_procedure_id', 'treatment_procedures.id'))
                ->get();

            $concept = FeeConcept::where('category', 'tratamiento')->where('is_active', true)->first();

            foreach ($procedures as $procedure) {
                Charge::create([
                    'payer_type' => $plan->patient->getMorphClass(),
                    'payer_id' => $plan->patient_id,
                    'fee_concept_id' => $concept?->id,
                    'treatment_procedure_id' => $procedure->id,
                    'description' => trim(($procedure->procedure?->name ?? $procedure->description).($procedure->tooth ? " · diente {$procedure->tooth}" : '')),
                    'amount' => $procedure->cost,
                    'due_date' => $dueDate,
                    'created_by' => $user->id,
                ]);
            }

            return $procedures->count();
        });
    }
}
