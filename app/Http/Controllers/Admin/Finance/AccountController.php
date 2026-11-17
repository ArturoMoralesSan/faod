<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\CashRegisterSession;
use App\Models\Charge;
use App\Models\FeeConcept;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\StudentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Estado de cuenta de un alumno o paciente y búsqueda de pagadores.
 */
class AccountController extends Controller
{
    public function show(string $type, int $id): Response
    {
        abort_unless(isset(Charge::PAYER_TYPES[$type]), 404);

        $payer = Charge::PAYER_TYPES[$type]::with('people')->findOrFail($id);

        $charges = Charge::forPayer($payer)
            ->with(['concept:id,code,name', 'academicPeriod:id,name'])
            ->orderByRaw("case when status in ('pendiente', 'parcial') then 0 else 1 end")
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->latest('id')
            ->get();

        $payments = Payment::query()
            ->where('payer_type', $payer->getMorphClass())
            ->where('payer_id', $payer->id)
            ->with(['allocations.charge:id,description', 'receiver:id,name'])
            ->latest('paid_at')
            ->get();

        return Inertia::render('admin/finance/Account', [
            'payer' => [
                'type' => $type,
                'id' => $payer->id,
                'name' => $payer->people->full_name,
                'code' => $type === 'student' ? $payer->registration_number : $payer->record_number,
                'phone' => $payer->people->phone,
                'email' => $payer->people->personal_email,
                'profile_url' => $type === 'student'
                    ? route('admin.people.show', $payer->people_id)
                    : route('admin.patients.show', $payer->id),
            ],
            'charges' => $charges,
            'payments' => $payments,
            'totals' => [
                'charged' => round($charges->where('status', '!=', 'cancelado')->sum(fn ($c) => $c->amount - $c->discount_amount), 2),
                'paid' => round($payments->whereNull('cancelled_at')->sum('amount'), 2),
                'balance' => round($charges->sum('balance'), 2),
                'overdue' => round($charges->filter(fn ($c) => $c->balance > 0 && $c->due_date && $c->due_date->isPast())->sum('balance'), 2),
            ],
            'openSessions' => CashRegisterSession::where('status', 'abierta')
                ->with('register:id,name')
                ->get(['id', 'cash_register_id', 'opened_at']),
            'concepts' => FeeConcept::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'amount']),
            'methods' => Payment::METHODS,
        ]);
    }

    /** Búsqueda de alumnos o pacientes para registrar adeudos. */
    public function search(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q'));
        $type = $request->input('type', 'patient');

        if (mb_strlen($search) < 2 || ! isset(Charge::PAYER_TYPES[$type])) {
            return response()->json([]);
        }

        $query = $type === 'student'
            ? StudentProfile::query()->where(fn ($q) => $q
                ->where('registration_number', 'like', "%{$search}%")
                ->orWhereHas('people', fn ($q) => $q->search($search)))
            : Patient::query()->where(fn ($q) => $q
                ->where('record_number', 'like', "%{$search}%")
                ->orWhereHas('people', fn ($q) => $q->search($search)));

        return response()->json(
            $query->with('people:id,first_name,middle_name,paternal_surname,maternal_surname,phone')
                ->limit(15)
                ->get()
                ->map(fn ($payer) => [
                    'id' => $payer->id,
                    'label' => ($type === 'student' ? $payer->registration_number : $payer->record_number).' · '.$payer->people->full_name,
                    'hint' => $payer->people->phone,
                ])
        );
    }
}
