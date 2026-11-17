<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\Charge;
use App\Models\Payment;
use App\Services\FinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pagos: registro desde el estado de cuenta, listado, recibo y cancelación.
 */
class PaymentController extends Controller
{
    public function __construct(private FinanceService $finance)
    {
    }

    public function index(Request $request): Response
    {
        $payments = Payment::query()
            ->with(['payer' => ChargeController::payerWithPeople(), 'receiver:id,name', 'session.register:id,name'])
            ->when($request->search, fn ($q, $search) => $q->where('folio', 'like', "%{$search}%"))
            ->when($request->method, fn ($q, $method) => $q->where('method', $method))
            ->when($request->date_from, fn ($q, $date) => $q->whereDate('paid_at', '>=', $date))
            ->when($request->date_to, fn ($q, $date) => $q->whereDate('paid_at', '<=', $date))
            ->when($request->status === 'cancelado', fn ($q) => $q->whereNotNull('cancelled_at'))
            ->when($request->status === 'vigente', fn ($q) => $q->whereNull('cancelled_at'))
            ->latest('paid_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $payments->getCollection()->each(fn (Payment $payment) => ChargeController::describePayer($payment));

        $totalQuery = Payment::query()
            ->whereNull('cancelled_at')
            ->when($request->date_from, fn ($q, $date) => $q->whereDate('paid_at', '>=', $date))
            ->when($request->date_to, fn ($q, $date) => $q->whereDate('paid_at', '<=', $date))
            ->when($request->method, fn ($q, $method) => $q->where('method', $method));

        return Inertia::render('admin/finance/Payments', [
            'payments' => $payments,
            'filters' => $request->only('search', 'method', 'date_from', 'date_to', 'status'),
            'methods' => Payment::METHODS,
            'total' => (float) $totalQuery->sum('amount'),
        ]);
    }

    public function store(Request $request, string $type, int $id): RedirectResponse
    {
        abort_unless(isset(Charge::PAYER_TYPES[$type]), 404);
        $payer = Charge::PAYER_TYPES[$type]::findOrFail($id);

        $data = $request->validate([
            'method' => ['required', Rule::in(Payment::METHODS)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'cash_register_session_id' => ['nullable', 'exists:cash_register_sessions,id'],
            'reference' => [Rule::requiredIf(in_array($request->input('method'), ['transferencia', 'deposito'], true)), 'nullable', 'string', 'max:100'],
            'paid_at' => ['nullable', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string'],
            'allocations' => ['nullable', 'array'],
            'allocations.*' => ['nullable', 'numeric', 'min:0'],
        ], [
            'reference.required' => 'Indica la referencia de la transferencia o depósito.',
        ]);

        $payment = $this->finance->registerPayment($payer, $data, $request->user());

        return redirect()
            ->route('admin.finance.payments.show', $payment)
            ->with('success', "Pago registrado con folio {$payment->folio}.");
    }

    /** Recibo imprimible. */
    public function show(Payment $payment): Response
    {
        $payment->load(['payer.people', 'allocations.charge:id,description,amount,discount_amount,paid_amount,status', 'receiver:id,name', 'canceller:id,name', 'session.register:id,name']);
        ChargeController::describePayer($payment);

        return Inertia::render('admin/finance/Receipt', [
            'payment' => $payment,
        ]);
    }

    public function cancel(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate(['cancellation_reason' => ['required', 'string', 'max:255']]);

        $this->finance->cancelPayment($payment, $data['cancellation_reason'], $request->user());

        return back()->with('success', "Pago {$payment->folio} cancelado.");
    }
}
