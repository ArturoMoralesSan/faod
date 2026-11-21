<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Charge;
use App\Models\Cohort;
use App\Models\FeeConcept;
use App\Models\TreatmentPlan;
use App\Services\FinanceService;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Adeudos de alumnos y pacientes.
 */
class ChargeController extends Controller
{
    public function __construct(private FinanceService $finance)
    {
    }

    public function index(Request $request): Response
    {
        $charges = Charge::query()
            ->with(['payer' => self::payerWithPeople(), 'concept:id,code,name', 'academicPeriod:id,name'])
            ->when($request->payer_type, fn ($q, $type) => $q->where('payer_type', Charge::PAYER_TYPES[$type] ?? '-'))
            ->when($request->status === 'vencido', fn ($q) => $q->overdue(), fn ($q) => $q->when($request->status, fn ($q, $status) => $q->where('status', $status)))
            ->when($request->fee_concept_id, fn ($q, $id) => $q->where('fee_concept_id', $id))
            ->when($request->search, fn ($q, $search) => $q->where('description', 'like', "%{$search}%"))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $charges->getCollection()->each(fn (Charge $charge) => $this->describePayer($charge));

        return Inertia::render('admin/finance/Charges', [
            'charges' => $charges,
            'filters' => $request->only('payer_type', 'status', 'fee_concept_id', 'search'),
            'totals' => [
                'pending' => (float) Charge::open()->selectRaw('coalesce(sum(amount - discount_amount - paid_amount), 0) as total')->value('total'),
                'overdue' => (float) Charge::overdue()->selectRaw('coalesce(sum(amount - discount_amount - paid_amount), 0) as total')->value('total'),
            ],
            'statuses' => [...Charge::STATUSES, 'vencido'],
            'concepts' => FeeConcept::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'category', 'amount']),
            'periods' => AcademicPeriod::orderByDesc('starts_at')->get(['id', 'name', 'is_current']),
            'cohorts' => Cohort::where('status', '!=', 'concluida')->orderByDesc('starts_at')->get(['id', 'name']),
            'payerSearchUrl' => route('admin.finance.payers.search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payer_type' => ['required', Rule::in(array_keys(Charge::PAYER_TYPES))],
            'payer_id' => ['required', 'integer'],
            'fee_concept_id' => ['nullable', 'exists:fee_concepts,id'],
            'academic_period_id' => ['nullable', 'exists:academic_periods,id'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $payer = Charge::PAYER_TYPES[$data['payer_type']]::findOrFail($data['payer_id']);

        Charge::create([
            ...$data,
            'payer_type' => $payer->getMorphClass(),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Adeudo registrado.');
    }

    /** Cuota para todos los alumnos activos (o de una generación). */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fee_concept_id' => ['required', 'exists:fee_concepts,id'],
            'academic_period_id' => ['nullable', 'exists:academic_periods,id'],
            'cohort_id' => ['nullable', 'exists:cohorts,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'gt:0'],
            'due_date' => ['nullable', 'date'],
        ]);

        $created = $this->finance->chargeStudents(FeeConcept::findOrFail($data['fee_concept_id']), $data, $request->user());

        return back()->with('success', "Se generaron {$created} adeudo(s). Los alumnos que ya tenían esta cuota no se duplicaron.");
    }

    public function discount(Request $request, Charge $charge): RedirectResponse
    {
        $data = $request->validate([
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'discount_reason' => ['required', 'string', 'max:255'],
        ]);

        $this->finance->applyDiscount($charge, (float) $data['discount_amount'], $data['discount_reason'], $request->user());

        return back()->with('success', 'Descuento aplicado.');
    }

    public function cancel(Request $request, Charge $charge): RedirectResponse
    {
        $data = $request->validate(['cancellation_reason' => ['required', 'string', 'max:255']]);

        if ($charge->paid_amount > 0) {
            return back()->with('error', 'El adeudo tiene pagos aplicados; cancela primero los pagos.');
        }

        $charge->update(['status' => 'cancelado', 'cancellation_reason' => $data['cancellation_reason']]);

        return back()->with('success', 'Adeudo cancelado.');
    }

    /** Cargos al paciente por los procedimientos realizados de un plan. */
    public function fromTreatmentPlan(Request $request, TreatmentPlan $treatmentPlan): RedirectResponse
    {
        $created = $this->finance->chargeTreatmentPlan($treatmentPlan, $request->user(), $request->input('due_date'));

        return back()->with(
            $created ? 'success' : 'error',
            $created ? "Se generaron {$created} cargo(s) al paciente." : 'No hay procedimientos realizados pendientes de cobro.'
        );
    }

    /** Carga el pagador (alumno o paciente) con su persona sin consultas N+1. */
    public static function payerWithPeople(): \Closure
    {
        return fn (MorphTo $morph) => $morph->morphWith(
            collect(Charge::PAYER_TYPES)->mapWithKeys(fn ($class) => [$class => ['people']])->all()
        );
    }

    /** Agrega nombre y enlace de la cuenta del pagador para la vista. */
    public static function describePayer(Charge|\App\Models\Payment $record): void
    {
        $payer = $record->payer;
        $type = ($record->payer_type ? Charge::payerAlias($record->payer_type) : null) ?? 'patient';

        $record->setAttribute('payer_info', match (true) {
            (bool) $payer => [
                'type' => $type,
                'id' => $payer->id,
                'name' => $payer->people?->full_name,
                'code' => $type === 'student' ? $payer->registration_number : $payer->record_number,
                'url' => route('admin.finance.accounts.show', ['type' => $type, 'id' => $payer->id]),
            ],
            // Ingreso sin alumno ni paciente: se muestra el nombre capturado.
            $record instanceof \App\Models\Payment && $record->payer_type === null => [
                'type' => 'externo',
                'id' => null,
                'name' => $record->payer_name ?? 'Sin nombre',
                'code' => $record->concept ?? '',
                'url' => null,
            ],
            default => null,
        });

        $record->unsetRelation('payer');
    }
}
