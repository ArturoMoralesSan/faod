<?php

namespace Database\Seeders\Demo;

use App\Models\AcademicPeriod;
use App\Models\CashRegister;
use App\Models\CashRegisterSession;
use App\Models\Charge;
use App\Models\Clinic;
use App\Models\Cohort;
use App\Models\Expense;
use App\Models\FeeConcept;
use App\Models\Patient;
use App\Models\StudentProfile;
use App\Models\Supplier;
use App\Models\TreatmentPlan;
use App\Models\User;
use App\Services\FinanceService;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

/**
 * Conceptos de cobro, cajas con sus cortes, cuotas de alumnos, cargos por
 * tratamientos, pagos, un descuento, un pago cancelado y gastos. Los pagos se
 * registran a mano, como en la operación real (FinanceService).
 */
class FaodFinanceDemoSeeder extends Seeder
{
    use DemoHelpers;

    private FinanceService $finance;

    private User $cashier;

    private User $clinicCashier;

    /** @var array<string, CashRegisterSession> corte por caja y fecha */
    private array $sessions = [];

    public function run(FinanceService $finance): void
    {
        $this->finance = $finance;
        $cashiers = User::whereHas('people.staffProfile', fn ($q) => $q->whereIn('position', ['Cajera', 'Cajero']))->get();
        $this->cashier = $cashiers->first();
        $this->clinicCashier = $cashiers->last();
        $period = AcademicPeriod::current();

        $concepts = collect([
            ['INS-LCD', 'Cuota de inscripción semestral (licenciatura)', 'inscripcion', 2800],
            ['LAB-LCD', 'Cuota de laboratorio y clínica', 'laboratorio', 650],
            ['COL-POS', 'Colegiatura semestral de posgrado', 'colegiatura', 18000],
            ['CONST', 'Constancia de estudios', 'constancia', 120],
            ['CRED', 'Reposición de credencial', 'otro', 150],
            ['TRAT', 'Tratamiento dental en clínica', 'tratamiento', null],
            ['CURSO', 'Inscripción a curso de educación continua', 'curso', null],
        ])->mapWithKeys(fn ($c) => [$c[0] => FeeConcept::create(['code' => $c[0], 'name' => $c[1], 'category' => $c[2], 'amount' => $c[3], 'is_active' => true])]);

        $general = CashRegister::create(['name' => 'Caja general', 'is_active' => true]);
        $clinics = CashRegister::create(['name' => 'Caja de clínicas', 'clinic_id' => Clinic::firstWhere('code', 'CLIN-LIC')?->id, 'is_active' => true]);

        // Cortes de los últimos diez días hábiles (el de hoy queda abierto).
        $days = collect();
        for ($date = today(); $days->count() < 10; $date = $date->copy()->subDay()) {
            if (! $date->isWeekend()) {
                $days->prepend($date->copy());
            }
        }

        foreach ($days as $day) {
            foreach ([[$general, $this->cashier], [$clinics, $this->clinicCashier]] as [$register, $user]) {
                $this->sessions[$register->id.'|'.$day->toDateString()] = CashRegisterSession::create([
                    'cash_register_id' => $register->id,
                    'status' => 'abierta',
                    'opening_amount' => 500,
                    'opened_by' => $user->id,
                    'opened_at' => $day->copy()->setTime(8, 0),
                ]);
            }
        }

        $this->studentCharges($concepts, $period, $general, $days);
        $this->patientCharges($clinics, $days);
        $this->expenses($general, $clinics, $days);
        $this->closeSessions($days);
    }

    private function studentCharges($concepts, AcademicPeriod $period, CashRegister $register, $days): void
    {
        $dueDate = $period->starts_at->copy()->addDays(30)->toDateString();
        $admin = User::where('email', 'admin@odontologia.test')->first() ?? $this->cashier;

        foreach (Cohort::with('academicProgram')->where('status', 'en_curso')->get() as $cohort) {
            $isLcd = $cohort->academicProgram->code === 'LCD';

            foreach ($isLcd ? ['INS-LCD', 'LAB-LCD'] : ['COL-POS'] as $code) {
                $this->finance->chargeStudents($concepts[$code], [
                    'cohort_id' => $cohort->id,
                    'academic_period_id' => $period->id,
                    'due_date' => $dueDate,
                    'description' => "{$concepts[$code]->name} {$period->code}",
                ], $admin);
            }
        }

        // Una beca del 50 % en la inscripción.
        $scholarship = Charge::where('fee_concept_id', $concepts['INS-LCD']->id)->inRandomOrder()->first();
        $this->finance->applyDiscount($scholarship, $scholarship->amount / 2, 'Beca de excelencia académica (50 %)', $admin);

        foreach (Charge::where('payer_type', (new StudentProfile)->getMorphClass())->where('status', '!=', 'pagado')->get() as $charge) {
            $roll = mt_rand(1, 100);

            if ($roll > 85) {
                continue; // Sigue pendiente.
            }

            $amount = $charge->balance;

            if ($roll > 75) {
                $amount = round($amount / 2, -1); // Pago parcial.
            }

            $this->pay($charge->payer, $charge, $amount, $register, $days, $period->starts_at->copy()->addDays(mt_rand(3, 40)));
        }

        // Constancias pagadas en ventanilla.
        foreach (StudentProfile::where('status', 'active')->inRandomOrder()->limit(5)->get() as $student) {
            $charge = Charge::create([
                'payer_type' => $student->getMorphClass(),
                'payer_id' => $student->id,
                'fee_concept_id' => $concepts['CONST']->id,
                'academic_period_id' => $period->id,
                'description' => 'Constancia de estudios',
                'amount' => 120,
                'created_by' => $this->cashier->id,
            ]);

            $this->pay($student, $charge, 120, $register, $days, $days->random());
        }
    }

    private function patientCharges(CashRegister $register, $days): void
    {
        $plans = TreatmentPlan::whereHas('procedures', fn ($q) => $q->where('status', 'realizado')->where('cost', '>', 0))->get();

        foreach ($plans as $plan) {
            $this->finance->chargeTreatmentPlan($plan, $this->clinicCashier);
        }

        $charges = Charge::where('payer_type', (new Patient)->getMorphClass())->with('treatmentProcedure')->get();

        foreach ($charges as $charge) {
            if ($this->chance(22)) {
                continue; // Adeudo pendiente.
            }

            $performed = Date::parse($charge->treatmentProcedure?->performed_at ?? today());
            $amount = $this->chance(85) ? $charge->balance : round($charge->balance * 0.5, -1);

            $this->pay($charge->payer, $charge, $amount, $register, $days, $performed);
        }

        // Un pago capturado por error y cancelado (en el corte abierto de hoy).
        $charge = Charge::where('payer_type', (new Patient)->getMorphClass())->where('status', 'pendiente')->first();

        if ($charge) {
            $payment = $this->pay($charge->payer, $charge, $charge->balance, $register, $days, today());
            $payment && $this->finance->cancelPayment($payment, 'Se capturó al paciente equivocado.', $this->clinicCashier);
        }
    }

    /**
     * Pago en efectivo si cae en uno de los cortes; si no, con tarjeta o transferencia.
     */
    private function pay($payer, Charge $charge, float $amount, CashRegister $register, $days, CarbonInterface $date)
    {
        if ($amount <= 0) {
            return null;
        }

        $date = $this->weekday($date)->min(today());
        $session = $this->sessions[$register->id.'|'.$date->toDateString()] ?? null;
        $method = $session && $this->chance(75) ? 'efectivo' : $this->pick(['tarjeta', 'transferencia', 'tarjeta', 'deposito']);
        $user = $register->clinic_id ? $this->clinicCashier : $this->cashier;

        return $this->finance->registerPayment($payer, [
            'amount' => $amount,
            'method' => $method,
            'cash_register_session_id' => $session?->id,
            'reference' => $method === 'efectivo' ? null : strtoupper(substr(md5($charge->id.$amount), 0, 8)),
            // Hoy, solo horas que ya pasaron.
            'paid_at' => $date->copy()->setTime(mt_rand(8, $date->isToday() ? max(8, min(14, now()->hour - 1)) : 14), mt_rand(0, 59)),
            'allocations' => [$charge->id => $amount],
        ], $user);
    }

    private function expenses(CashRegister $general, CashRegister $clinics, $days): void
    {
        $supplier = Supplier::first();
        $lic = Clinic::firstWhere('code', 'CLIN-LIC');

        $rows = [
            ['material', 'Compra urgente de guantes y cubrebocas', 1450, 'efectivo', $clinics, $supplier, $lic],
            ['mantenimiento', 'Mantenimiento preventivo del compresor', 2800, 'transferencia', $general, null, $lic],
            ['papeleria', 'Hojas y tóner para historias clínicas', 960, 'efectivo', $general, null, null],
            ['servicios', 'Recarga de gas para el laboratorio', 1200, 'efectivo', $general, null, null],
            ['otro', 'Garrafones de agua para las clínicas', 300, 'efectivo', $clinics, null, $lic],
        ];

        foreach ($rows as $i => [$category, $description, $amount, $method, $register, $supplierModel, $clinic]) {
            $day = $days[min($days->count() - 1, $i * 2)];
            $session = $method === 'efectivo' ? $this->sessions[$register->id.'|'.$day->toDateString()] : null;

            Expense::create([
                'category' => $category,
                'description' => $description,
                'amount' => $amount,
                'method' => $method,
                'paid_at' => $day->copy()->setTime(12, 30),
                'supplier_id' => $supplierModel?->id,
                'clinic_id' => $clinic?->id,
                'cash_register_session_id' => $session?->id,
                'reference' => $method === 'efectivo' ? null : 'SPEI-'.mt_rand(100000, 999999),
                'created_by' => ($register->clinic_id ? $this->clinicCashier : $this->cashier)->id,
            ]);
        }
    }

    /** Cierra todos los cortes menos los de hoy; uno con faltante y depósito al banco. */
    private function closeSessions($days): void
    {
        foreach ($this->sessions as $key => $session) {
            $date = Date::parse(explode('|', $key)[1]);

            if ($date->isToday()) {
                continue;
            }

            $summary = $session->summary();

            if ($summary['expected_cash'] > 3000) {
                $session->cashMovements()->create([
                    'type' => 'retiro',
                    'amount' => 2500,
                    'reason' => 'Depósito al banco',
                    'user_id' => $session->opened_by,
                ]);
                $summary = $session->summary();
            }

            $short = $this->chance(10) ? 50 : 0;

            $session->update([
                'status' => 'cerrada',
                'expected_cash' => $summary['expected_cash'],
                'counted_cash' => $summary['expected_cash'] - $short,
                'closed_by' => $session->opened_by,
                'closed_at' => $date->copy()->setTime(15, 5),
                'notes' => $short ? 'Faltante de $50; se levantó acta.' : null,
            ]);
        }
    }
}
