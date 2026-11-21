<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\CashMovement;
use App\Models\CashRegisterSession;
use App\Models\Clinic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Cajas, apertura de turno y corte de caja.
 */
class CashRegisterController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/finance/CashRegisters', [
            'registers' => CashRegister::query()
                ->with(['clinic:id,name', 'openSession.opener:id,name'])
                ->orderBy('name')
                ->get(),
            'sessions' => CashRegisterSession::query()
                ->with(['register:id,name', 'opener:id,name', 'closer:id,name'])
                ->latest('opened_at')
                ->limit(30)
                ->get(),
            'clinics' => Clinic::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        CashRegister::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'clinic_id' => ['nullable', Rule::exists('clinics', 'id')->whereNull('deleted_at')],
        ]));

        return back()->with('success', 'Caja registrada.');
    }

    public function open(Request $request, CashRegister $cashRegister): RedirectResponse
    {
        $data = $request->validate(['opening_amount' => ['required', 'numeric', 'min:0']]);

        if ($cashRegister->openSession()->exists()) {
            return back()->with('error', 'La caja ya tiene un turno abierto.');
        }

        $session = $cashRegister->sessions()->create([
            'status' => 'abierta',
            'opening_amount' => $data['opening_amount'],
            'opened_by' => $request->user()->id,
            'opened_at' => now(),
        ]);

        return redirect()->route('admin.finance.cash-sessions.show', $session)->with('success', 'Caja abierta.');
    }

    /** Corte de caja: resumen, cobros y gastos del turno. */
    public function session(CashRegisterSession $cashSession): Response
    {
        $cashSession->load(['register:id,name', 'opener:id,name', 'closer:id,name']);

        $payments = $cashSession->payments()->with(['payer' => ChargeController::payerWithPeople()])->latest('paid_at')->get();
        $payments->each(fn ($payment) => ChargeController::describePayer($payment));

        return Inertia::render('admin/finance/CashSession', [
            'session' => $cashSession,
            'summary' => $cashSession->summary(),
            'payments' => $payments,
            'expenses' => $cashSession->expenses()->latest('paid_at')->get(['id', 'description', 'amount', 'method', 'paid_at']),
            'movements' => $cashSession->cashMovements()->with('user:id,name')->get(),
        ]);
    }

    /** Depósito o retiro de efectivo que no es un cobro ni un gasto. */
    public function movement(Request $request, CashRegisterSession $cashSession): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(CashMovement::TYPES)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        if ($cashSession->status !== 'abierta') {
            return back()->with('error', 'El turno ya está cerrado.');
        }

        if ($data['type'] === 'retiro' && $data['amount'] > $cashSession->summary()['expected_cash'] + 0.004) {
            return back()->withErrors(['amount' => 'No hay suficiente efectivo en la caja para ese retiro.']);
        }

        $cashSession->cashMovements()->create([...$data, 'user_id' => $request->user()->id]);

        return back()->with('success', $data['type'] === 'retiro' ? 'Retiro registrado.' : 'Depósito registrado.');
    }

    public function close(Request $request, CashRegisterSession $cashSession): RedirectResponse
    {
        $data = $request->validate([
            'counted_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($cashSession->status !== 'abierta') {
            return back()->with('error', 'El turno ya está cerrado.');
        }

        $cashSession->update([
            'status' => 'cerrada',
            'expected_cash' => $cashSession->summary()['expected_cash'],
            'counted_cash' => $data['counted_cash'],
            'notes' => $data['notes'] ?? null,
            'closed_by' => $request->user()->id,
            'closed_at' => now(),
        ]);

        return back()->with('success', 'Corte de caja realizado.');
    }
}
