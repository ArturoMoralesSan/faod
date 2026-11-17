<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\CashRegisterSession;
use App\Models\Clinic;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gastos de la facultad (con comprobantes adjuntos).
 */
class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Expense::query()
            ->when($request->category, fn ($q, $category) => $q->where('category', $category))
            ->when($request->clinic_id, fn ($q, $id) => $q->where('clinic_id', $id))
            ->when($request->date_from, fn ($q, $date) => $q->whereDate('paid_at', '>=', $date))
            ->when($request->date_to, fn ($q, $date) => $q->whereDate('paid_at', '<=', $date))
            ->when($request->search, fn ($q, $search) => $q->where('description', 'like', "%{$search}%"));

        return Inertia::render('admin/finance/Expenses', [
            'expenses' => (clone $query)
                ->with(['supplier:id,name', 'clinic:id,name', 'creator:id,name', 'attachments', 'session.register:id,name'])
                ->latest('paid_at')
                ->paginate(25)
                ->withQueryString(),
            'total' => (float) $query->sum('amount'),
            'filters' => $request->only('category', 'clinic_id', 'date_from', 'date_to', 'search'),
            'categories' => Expense::CATEGORIES,
            'methods' => Payment::METHODS,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'clinics' => Clinic::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'openSessions' => CashRegisterSession::where('status', 'abierta')->with('register:id,name')->get(['id', 'cash_register_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Expense::create([...$this->validated($request), 'created_by' => $request->user()->id]);

        return back()->with('success', 'Gasto registrado.');
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $this->assertSessionOpen($expense);
        $expense->update($this->validated($request));

        return back()->with('success', 'Gasto actualizado.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->assertSessionOpen($expense);
        $expense->delete();

        return back()->with('success', 'Gasto eliminado.');
    }

    private function assertSessionOpen(Expense $expense): void
    {
        if ($expense->session && $expense->session->status === 'cerrada') {
            throw ValidationException::withMessages(['expense' => 'El gasto pertenece a un corte de caja cerrado.']);
        }
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(Expense::CATEGORIES)],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', Rule::in(Payment::METHODS)],
            'paid_at' => ['required', 'date', 'before_or_equal:now'],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
            'clinic_id' => ['nullable', Rule::exists('clinics', 'id')->whereNull('deleted_at')],
            'cash_register_session_id' => [
                Rule::requiredIf($request->input('method') === 'efectivo'),
                'nullable',
                Rule::exists('cash_register_sessions', 'id')->where('status', 'abierta'),
            ],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ], [
            'cash_register_session_id.required' => 'Un gasto en efectivo sale de una caja abierta.',
            'cash_register_session_id.exists' => 'La caja indicada no está abierta.',
        ]);

        if ($data['method'] !== 'efectivo') {
            $data['cash_register_session_id'] = null;
        }

        return $data;
    }
}
