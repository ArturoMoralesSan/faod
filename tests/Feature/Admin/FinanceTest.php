<?php

namespace Tests\Feature\Admin;

use App\Models\CashRegister;
use App\Models\CashRegisterSession;
use App\Models\Charge;
use App\Models\FeeConcept;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\People;
use App\Models\StudentProfile;
use App\Models\TreatmentPlan;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    private CashRegisterSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->create()->assignRole('SuperAdmin'));

        $people = People::create(['first_name' => 'Pago', 'paternal_surname' => 'Paciente']);
        $this->patient = Patient::create(['people_id' => $people->id, 'registered_at' => now()]);

        $register = CashRegister::create(['name' => 'Caja 1']);
        $this->post(route('admin.finance.cash-registers.open', $register), ['opening_amount' => 500])->assertRedirect();
        $this->session = CashRegisterSession::first();
    }

    private function charge(float $amount, ?string $due = null): Charge
    {
        $this->post(route('admin.finance.charges.store'), [
            'payer_type' => 'patient',
            'payer_id' => $this->patient->id,
            'description' => 'Resina',
            'amount' => $amount,
            'due_date' => $due,
        ])->assertSessionHasNoErrors();

        return Charge::latest('id')->first();
    }

    private function pay(array $data)
    {
        return $this->post(route('admin.finance.payments.store', ['type' => 'patient', 'id' => $this->patient->id]), $data);
    }

    public function test_payment_is_applied_oldest_first_and_gets_folio(): void
    {
        $old = $this->charge(400, now()->subDays(5)->toDateString());
        $new = $this->charge(300, now()->addDays(5)->toDateString());

        $this->pay(['method' => 'efectivo', 'amount' => 500, 'cash_register_session_id' => $this->session->id])
            ->assertSessionHasNoErrors();

        $payment = Payment::first();
        $this->assertSame(sprintf('R-%06d', $payment->id), $payment->folio);
        $this->assertSame('pagado', $old->fresh()->status);
        $this->assertSame('parcial', $new->fresh()->status);
        $this->assertEquals(200, $new->fresh()->balance);

        // No se puede pagar más que el saldo.
        $this->pay(['method' => 'efectivo', 'amount' => 250, 'cash_register_session_id' => $this->session->id])
            ->assertSessionHasErrors('amount');

        // Efectivo sin caja abierta.
        $this->pay(['method' => 'efectivo', 'amount' => 50])->assertSessionHasErrors('cash_register_session_id');

        // Transferencia exige referencia.
        $this->pay(['method' => 'transferencia', 'amount' => 50])->assertSessionHasErrors('reference');
    }

    public function test_explicit_allocation_cancellation_and_discount(): void
    {
        $a = $this->charge(400);
        $b = $this->charge(300);

        // La suma aplicada debe coincidir con el monto.
        $this->pay(['method' => 'tarjeta', 'amount' => 300, 'allocations' => [$b->id => 200]])->assertSessionHasErrors('allocations');

        $this->pay(['method' => 'tarjeta', 'amount' => 300, 'allocations' => [$b->id => 300]])->assertSessionHasNoErrors();
        $this->assertSame('pagado', $b->fresh()->status);
        $this->assertSame('pendiente', $a->fresh()->status);

        $payment = Payment::first();
        $this->patch(route('admin.finance.payments.cancel', $payment), ['cancellation_reason' => 'Error'])->assertSessionHasNoErrors();
        $this->assertNotNull($payment->fresh()->cancelled_at);
        $this->assertSame('pendiente', $b->fresh()->status);

        $this->patch(route('admin.finance.charges.discount', $a), ['discount_amount' => 100, 'discount_reason' => 'Beca'])->assertSessionHasNoErrors();
        $this->assertEquals(300, $a->fresh()->balance);

        // Descuento por el total: queda saldado.
        $this->patch(route('admin.finance.charges.discount', $a), ['discount_amount' => 400, 'discount_reason' => 'Beca completa']);
        $this->assertSame('pagado', $a->fresh()->status);

        $this->get(route('admin.finance.accounts.show', ['type' => 'patient', 'id' => $this->patient->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/finance/Account')->where('totals.balance', 300));

        $this->get(route('admin.finance.payments.show', $payment))->assertOk();
    }

    public function test_cash_session_summary_and_close(): void
    {
        $this->charge(1000);
        $this->pay(['method' => 'efectivo', 'amount' => 300, 'cash_register_session_id' => $this->session->id]);
        $this->pay(['method' => 'tarjeta', 'amount' => 200, 'cash_register_session_id' => $this->session->id]);

        $this->post(route('admin.finance.expenses.store'), [
            'category' => 'papeleria',
            'description' => 'Hojas',
            'amount' => 50,
            'method' => 'efectivo',
            'paid_at' => now()->format('Y-m-d\TH:i'),
            'cash_register_session_id' => $this->session->id,
        ])->assertSessionHasNoErrors();

        $summary = $this->session->fresh()->summary();
        $this->assertEquals(500, $summary['total_collected']);
        // 500 fondo + 300 efectivo − 50 gasto
        $this->assertEquals(750, $summary['expected_cash']);

        $this->patch(route('admin.finance.cash-sessions.close', $this->session), ['counted_cash' => 740])->assertSessionHasNoErrors();
        $session = $this->session->fresh();
        $this->assertSame('cerrada', $session->status);
        $this->assertEquals(750, $session->expected_cash);

        // Pago de un corte cerrado no se cancela.
        $this->patch(route('admin.finance.payments.cancel', Payment::first()), ['cancellation_reason' => 'X'])->assertSessionHasErrors('payment');

        $this->get(route('admin.finance.cash-sessions.show', $session))->assertOk();
        $this->get(route('admin.finance.cash-registers.index'))->assertOk();
        $this->get(route('admin.finance.payments.index'))->assertOk();
        $this->get(route('admin.finance.expenses.index'))->assertOk();
    }

    public function test_bulk_student_fees_and_treatment_charges(): void
    {
        $concept = FeeConcept::create(['code' => 'COL', 'name' => 'Colegiatura', 'category' => 'colegiatura', 'amount' => 1500]);

        $programId = DB::table('academic_programs')->insertGetId(['type' => 'licenciatura', 'code' => 'LCD', 'name' => 'LCD', 'created_at' => now(), 'updated_at' => now()]);
        $cohortId = DB::table('cohorts')->insertGetId(['academic_program_id' => $programId, 'name' => 'G1', 'starts_at' => now(), 'status' => 'en_curso', 'created_at' => now(), 'updated_at' => now()]);

        foreach (['A1', 'A2'] as $registration) {
            $people = People::create(['first_name' => 'Al', 'paternal_surname' => $registration]);
            $student = StudentProfile::create(['people_id' => $people->id, 'registration_number' => $registration]);
            DB::table('student_enrollments')->insert([
                'student_profile_id' => $student->id,
                'academic_program_id' => $programId,
                'cohort_id' => $cohortId,
                'status' => 'activo',
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->post(route('admin.finance.charges.bulk'), ['fee_concept_id' => $concept->id, 'due_date' => now()->addMonth()->toDateString()])
            ->assertSessionHasNoErrors();
        $this->assertSame(2, Charge::where('fee_concept_id', $concept->id)->count());

        // No duplica.
        $this->post(route('admin.finance.charges.bulk'), ['fee_concept_id' => $concept->id]);
        $this->assertSame(2, Charge::where('fee_concept_id', $concept->id)->count());

        $plan = TreatmentPlan::create(['patient_id' => $this->patient->id, 'title' => 'Plan', 'started_at' => now()]);
        $plan->procedures()->create(['patient_id' => $this->patient->id, 'description' => 'Resina', 'status' => 'realizado', 'cost' => 400]);
        $plan->procedures()->create(['patient_id' => $this->patient->id, 'description' => 'Extracción', 'status' => 'planeado', 'cost' => 300]);

        $this->post(route('admin.finance.treatment-plans.charges', $plan))->assertSessionHas('success');
        $this->assertSame(1, Charge::whereNotNull('treatment_procedure_id')->count());

        // Ya cobrado: no se repite.
        $this->post(route('admin.finance.treatment-plans.charges', $plan))->assertSessionHas('error');

        $this->get(route('admin.finance.charges.index', ['payer_type' => 'student']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('charges.data', 2));

        $this->getJson(route('admin.finance.payers.search', ['type' => 'student', 'q' => 'A1']))->assertJsonCount(1);
    }
}
