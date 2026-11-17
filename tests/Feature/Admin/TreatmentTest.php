<?php

namespace Tests\Feature\Admin;

use App\Models\Patient;
use App\Models\People;
use App\Models\Procedure;
use App\Models\TreatmentPlan;
use App\Models\TreatmentProcedure;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TreatmentTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->create()->assignRole('SuperAdmin'));

        $people = People::create(['first_name' => 'Rosa', 'paternal_surname' => 'Paciente']);
        $this->patient = Patient::create(['people_id' => $people->id, 'registered_at' => now()]);
    }

    private function plan(): TreatmentPlan
    {
        $this->post(route('admin.patients.treatment-plans.store', $this->patient), [
            'title' => 'Rehabilitación',
            'status' => 'en_proceso',
            'started_at' => now()->toDateString(),
        ])->assertRedirect();

        return TreatmentPlan::latest('id')->first();
    }

    public function test_plan_with_stages_procedures_and_materials(): void
    {
        $plan = $this->plan();
        $this->assertSame('pendiente', $plan->review_status);

        $this->post(route('admin.treatment-plans.stages.store', $plan), ['name' => 'Fase higiénica'])->assertSessionHasNoErrors();
        $stage = $plan->stages()->first();
        $this->assertSame(1, $stage->order);

        $resin = Procedure::firstWhere('code', 'OP-01');

        $this->post(route('admin.treatment-plans.procedures.store', $plan), [
            'treatment_stage_id' => $stage->id,
            'procedure_id' => $resin->id,
            'tooth' => '36',
            'surfaces' => 'OM',
            'status' => 'planeado',
        ])->assertSessionHasNoErrors();

        $procedure = TreatmentProcedure::first();
        // Costo tomado del catálogo; no requiere validación mientras no se realiza.
        $this->assertEquals($resin->default_cost, $procedure->cost);
        $this->assertNull($procedure->review_status);
        $this->assertSame($this->patient->id, $procedure->patient_id);

        $this->post(route('admin.treatment-procedures.materials.store', $procedure), ['name' => 'Resina A2', 'quantity' => 1, 'unit' => 'g'])
            ->assertSessionHasNoErrors();

        $this->get(route('admin.treatment-plans.show', $plan))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/treatment-plans/Show')
                ->has('plan.stages', 1)
                ->has('plan.procedures.0.materials', 1));

        $this->get(route('admin.patients.show', $this->patient))
            ->assertInertia(fn (Assert $page) => $page
                ->has('patient.treatment_plans', 1)
                ->where('patient.treatment_plans.0.procedures_count', 1));
    }

    public function test_performed_procedure_requires_validation(): void
    {
        $plan = $this->plan();

        $this->post(route('admin.treatment-plans.procedures.store', $plan), [
            'description' => 'Ajuste oclusal',
            'status' => 'planeado',
        ])->assertSessionHasNoErrors();
        $procedure = TreatmentProcedure::first();

        $this->put(route('admin.treatment-procedures.update', $procedure), [
            'description' => 'Ajuste oclusal',
            'status' => 'realizado',
        ])->assertSessionHasNoErrors();

        $procedure->refresh();
        $this->assertSame('pendiente', $procedure->review_status);
        $this->assertNotNull($procedure->performed_at);

        $this->post(route('admin.reviews.store'), [
            'reviewable_type' => 'treatment_procedure',
            'reviewable_id' => $procedure->id,
            'decision' => 'autorizado',
        ])->assertSessionHasNoErrors();
        $this->assertSame('autorizado', $procedure->fresh()->review_status);

        // Aparece en revisiones mientras está pendiente; ya autorizado, no.
        $this->get(route('admin.reviews.index', ['scope' => 'all']))
            ->assertInertia(fn (Assert $page) => $page->has('items', 1)->where('items.0.type', 'treatment_plan'));

        // Regresar a planeado quita la validación.
        $this->put(route('admin.treatment-procedures.update', $procedure), [
            'description' => 'Ajuste oclusal',
            'status' => 'planeado',
        ]);
        $this->assertNull($procedure->fresh()->review_status);
    }

    public function test_plan_content_change_requires_new_authorization(): void
    {
        $plan = $this->plan();

        $this->post(route('admin.reviews.store'), [
            'reviewable_type' => 'treatment_plan',
            'reviewable_id' => $plan->id,
            'decision' => 'autorizado',
        ]);
        $this->assertSame('autorizado', $plan->fresh()->review_status);

        // Solo el estado: se conserva la autorización.
        $this->put(route('admin.treatment-plans.update', $plan), [
            'title' => 'Rehabilitación',
            'status' => 'concluido',
            'started_at' => $plan->started_at->toDateString(),
        ])->assertSessionHasNoErrors();
        $plan->refresh();
        $this->assertSame('autorizado', $plan->review_status);
        $this->assertNotNull($plan->completed_at);

        $this->put(route('admin.treatment-plans.update', $plan), [
            'title' => 'Rehabilitación completa',
            'status' => 'concluido',
            'started_at' => $plan->started_at->toDateString(),
        ]);
        $this->assertSame('pendiente', $plan->fresh()->review_status);
    }

    public function test_procedure_catalog(): void
    {
        $this->get(route('admin.procedures.index'))->assertOk();

        $this->post(route('admin.procedures.store'), ['code' => 'X-1', 'name' => 'Prueba', 'default_cost' => 100])
            ->assertSessionHasNoErrors();
        $this->post(route('admin.procedures.store'), ['code' => 'X-1', 'name' => 'Duplicado'])
            ->assertSessionHasErrors('code');
    }
}
