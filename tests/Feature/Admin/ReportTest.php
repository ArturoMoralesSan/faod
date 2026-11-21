<?php

namespace Tests\Feature\Admin;

use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\InventoryLot;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\People;
use App\Models\StudentProfile;
use App\Models\TreatmentPlan;
use App\Models\TreatmentProcedure;
use App\Models\User;
use App\Models\Warehouse;
use App\Reports\ReportRegistry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->create(['name' => 'Admin Reportes'])->assignRole('SuperAdmin'));

        $people = People::create(['first_name' => 'Laura', 'paternal_surname' => 'Gómez']);
        $this->patient = Patient::create(['people_id' => $people->id, 'registered_at' => now()]);
    }

    private function payment(string $folio, string $method, float $amount, array $extra = []): Payment
    {
        return Payment::create([
            'folio' => $folio,
            'payer_type' => Patient::class,
            'payer_id' => $this->patient->id,
            'method' => $method,
            'amount' => $amount,
            'paid_at' => now(),
            ...$extra,
        ]);
    }

    public function test_every_report_renders_preview_pdf_and_csv(): void
    {
        $this->get(route('admin.reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reports/Index')
                ->has('reports', count(ReportRegistry::REPORTS)));

        foreach (ReportRegistry::all() as $report) {
            $this->get(route('admin.reports.show', $report->key()))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('admin/reports/Show')
                    ->where('report.key', $report->key())
                    ->has('columns', count($report->columns())));

            $pdf = $this->get(route('admin.reports.pdf', $report->key()));
            $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $pdf->getContent(), "PDF de {$report->key()}");

            $csv = $this->get(route('admin.reports.csv', $report->key()));
            $csv->assertOk();
            $this->assertStringStartsWith("\xEF\xBB\xBF", $csv->streamedContent(), "CSV de {$report->key()}");
        }

        $this->get(route('admin.reports.show', 'no-existe'))->assertNotFound();
    }

    public function test_payments_report_totals_and_filters(): void
    {
        $this->payment('R-000001', 'efectivo', 500);
        $this->payment('R-000002', 'tarjeta', 300);
        $this->payment('R-000003', 'efectivo', 200, ['cancelled_at' => now(), 'cancellation_reason' => 'Error']);
        // Fuera del mes en curso: no entra con el periodo por omisión.
        $this->payment('R-000004', 'efectivo', 999, ['paid_at' => now()->subMonths(2)]);

        $this->get(route('admin.reports.show', 'pagos'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('rows.total', 3)
                ->where('summary.0.value', '$800.00')
                ->where('summary.2.value', '$500.00')
                ->where('summary.4.value', '1')
                ->where('totals.amount', '$800.00')
                ->where('rows.data.0.cells.folio', 'R-000001')
                ->where('rows.data.0.raw.method', 'efectivo')
                ->where('rows.data.0.cells.method', 'Efectivo')
                ->where('rows.data.2.cells.state', 'Cancelado')
                // El cancelado muestra su importe pero no suma.
                ->where('rows.data.2.cells.amount', '$200.00')
                ->where('rows.data.2.excluded', true));

        // "Todo": sin límite de fechas.
        $this->get(route('admin.reports.show', ['pagos', 'all_dates' => 1]))
            ->assertInertia(fn (Assert $page) => $page->where('rows.total', 4)->where('values.all_dates', '1'));

        // Un valor que no está en las opciones se ignora.
        $this->get(route('admin.reports.show', ['pagos', 'method' => 'tarjeta', 'state' => 'inventado']))
            ->assertInertia(fn (Assert $page) => $page->where('rows.total', 1)->missing('values.state'));

        $csv = $this->get(route('admin.reports.csv', ['pagos', 'method' => 'efectivo']))->streamedContent();
        $lines = array_values(array_filter(explode("\n", trim($csv))));
        $this->assertCount(3, $lines);
        $this->assertStringContainsString('Folio,Fecha,Pagador', $lines[0]);
        $this->assertStringContainsString('R-000001', $lines[1]);
        $this->assertStringEndsWith(',500.00', trim($lines[1]));
    }

    public function test_stock_report_levels_and_value(): void
    {
        $warehouse = Warehouse::firstWhere('code', 'ALM');
        $gloves = InventoryItem::create(['code' => 'GUA', 'name' => 'Guantes', 'unit' => 'caja', 'minimum_stock' => 10]);
        $resin = InventoryItem::create(['code' => 'RES', 'name' => 'Resina', 'unit' => 'jeringa', 'minimum_stock' => 2]);
        InventoryItem::create(['code' => 'ANE', 'name' => 'Anestesia', 'unit' => 'cartucho', 'minimum_stock' => 5]);

        InventoryLot::create(['inventory_item_id' => $gloves->id, 'warehouse_id' => $warehouse->id, 'quantity' => 4, 'unit_cost' => 100]);
        InventoryLot::create(['inventory_item_id' => $resin->id, 'warehouse_id' => $warehouse->id, 'quantity' => 6, 'unit_cost' => 50, 'expires_at' => now()->addMonth()->toDateString()]);

        $this->get(route('admin.reports.show', 'existencias'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('rows.total', 3)
                ->where('summary.1.value', '1')
                ->where('summary.2.value', '1')
                ->where('summary.3.value', '$700.00')
                ->where('rows.data.0.raw.level', 'sin_existencia')
                ->where('rows.data.1.cells.level', 'Bajo mínimo')
                ->where('rows.data.2.cells.next_expiry', now()->addMonth()->format('d/m/Y')));

        $this->get(route('admin.reports.show', ['existencias', 'level' => 'bajo_minimo']))
            ->assertInertia(fn (Assert $page) => $page->where('rows.total', 1)->where('rows.data.0.cells.code', 'GUA'));
    }

    public function test_student_productivity_groups_procedures(): void
    {
        $student = StudentProfile::create([
            'people_id' => People::create(['first_name' => 'Pedro', 'paternal_surname' => 'Ruiz'])->id,
            'registration_number' => 'A-100',
        ]);
        $plan = TreatmentPlan::create(['patient_id' => $this->patient->id, 'title' => 'Plan', 'status' => 'en_curso', 'review_status' => 'autorizado', 'started_at' => today()]);

        foreach ([['autorizado', 300], ['autorizado', 200], ['pendiente', 100], ['rechazado', null]] as [$review, $cost]) {
            TreatmentProcedure::create([
                'treatment_plan_id' => $plan->id,
                'patient_id' => $this->patient->id,
                'description' => 'Profilaxis',
                'status' => 'realizado',
                'review_status' => $review,
                'cost' => $cost,
                'performed_at' => now(),
                'student_profile_id' => $student->id,
            ]);
        }

        $this->get(route('admin.reports.show', 'procedimientos-por-alumno'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('rows.total', 1)
                ->where('rows.data.0.cells.registration_number', 'A-100')
                ->where('rows.data.0.cells.total', '4')
                ->where('rows.data.0.cells.authorized', '2')
                ->where('rows.data.0.cells.pending', '1')
                ->where('rows.data.0.cells.rejected', '1')
                ->where('rows.data.0.cells.amount', '$600.00')
                ->where('summary.3.value', '50%'));

        $this->get(route('admin.reports.show', 'procedimientos'))
            ->assertInertia(fn (Assert $page) => $page->where('rows.total', 4)->where('totals.cost', '$600.00'));
    }

    public function test_each_report_requires_its_module_permission(): void
    {
        $role = Role::create(['name' => 'tesoreria', 'guard_name' => 'web']);
        $role->givePermissionTo(['reports.view', 'payments.view']);
        $this->actingAs(User::factory()->create()->assignRole('tesoreria'));

        $this->get(route('admin.reports.index'))
            ->assertInertia(fn (Assert $page) => $page->has('reports', 1)->where('reports.0.key', 'pagos'));

        $this->get(route('admin.reports.show', 'pagos'))->assertOk();
        $this->get(route('admin.reports.show', 'gastos'))->assertForbidden();
        $this->get(route('admin.reports.csv', 'gastos'))->assertForbidden();

        // Sin reports.view no entra a ninguno.
        $this->actingAs(User::factory()->create()->assignRole('alumno'));
        $this->get(route('admin.reports.index'))->assertForbidden();
    }

    public function test_pdf_marks_truncated_reports(): void
    {
        Expense::create(['category' => 'material', 'description' => 'Guantes', 'amount' => 150, 'method' => 'efectivo', 'paid_at' => now()]);

        $html = view('reports.pdf', [
            'report' => ReportRegistry::find('gastos'),
            'columns' => ReportRegistry::find('gastos')->columns(),
            'rows' => [],
            'totals' => [],
            'summary' => [],
            'filterLines' => ['Periodo: todas las fechas'],
            'truncated' => null,
            'institution' => 'Facultad de Odontología',
            'university' => 'Universidad',
            'generatedBy' => 'Admin',
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('No hay registros con los filtros seleccionados.', $html);
        $this->assertStringContainsString('Periodo: todas las fechas', $html);
    }
}
