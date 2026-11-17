<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicActivity;
use App\Models\ActivityParticipant;
use App\Models\People;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AcademicActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->create()->assignRole('SuperAdmin'));
    }

    private function activity(array $overrides = []): AcademicActivity
    {
        $this->post(route('admin.activities.store'), [
            'type' => 'curso',
            'title' => 'Actualización en endodoncia',
            'modality' => 'presencial',
            'starts_at' => now()->toDateString(),
            'status' => 'abierta',
            'capacity' => 2,
            'hours' => 20,
            'issues_certificate' => true,
            'min_attendance' => 80,
            ...$overrides,
        ])->assertSessionHasNoErrors();

        return AcademicActivity::latest('id')->first();
    }

    public function test_enrollment_capacity_and_certificates(): void
    {
        $activity = $this->activity();
        $registered = People::create(['first_name' => 'Ana', 'paternal_surname' => 'Registrada']);

        $this->post(route('admin.activities.participants.store', $activity), ['people_id' => $registered->id])->assertSessionHasNoErrors();
        // Duplicado.
        $this->post(route('admin.activities.participants.store', $activity), ['people_id' => $registered->id])->assertSessionHasErrors('people_id');
        // Externo nuevo.
        $this->post(route('admin.activities.participants.store', $activity), ['first_name' => 'Luis', 'paternal_surname' => 'Externo'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('people', ['first_name' => 'Luis', 'paternal_surname' => 'Externo']);
        // Cupo lleno.
        $this->post(route('admin.activities.participants.store', $activity), ['first_name' => 'Otro', 'paternal_surname' => 'Más'])->assertSessionHasErrors('people_id');

        [$first, $second] = $activity->participants()->orderBy('id')->get();

        $this->put(route('admin.activities.participants.update', $activity), [
            'participants' => [
                ['id' => $first->id, 'status' => 'acreditado', 'attendance' => 90, 'grade' => 9.5, 'payment_status' => 'no_aplica'],
                ['id' => $second->id, 'status' => 'acreditado', 'attendance' => 60, 'payment_status' => 'no_aplica'],
            ],
        ])->assertSessionHasNoErrors();

        // Solo quien cumple la asistencia mínima recibe constancia.
        $this->post(route('admin.activities.certificates.issue', $activity))->assertSessionHas('success');
        $this->assertMatchesRegularExpression('/^C-\d{4}-00001$/', $first->fresh()->certificate_folio);
        $this->assertNull($second->fresh()->certificate_folio);

        $this->get(route('admin.activities.participants.certificate', [$activity, $first]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/activities/Certificate'));

        // Con constancia no se puede eliminar.
        $this->delete(route('admin.activities.participants.destroy', [$activity, $first]))->assertSessionHas('error');
        $this->assertSame(2, ActivityParticipant::count());
    }

    public function test_pages_and_instructors(): void
    {
        $activity = $this->activity(['capacity' => null]);

        $this->post(route('admin.activities.instructors.store', $activity), ['role' => 'ponente'])->assertSessionHasErrors('name');
        $this->post(route('admin.activities.instructors.store', $activity), ['name' => 'Dra. Externa', 'role' => 'ponente'])->assertSessionHasNoErrors();

        $this->get(route('admin.activities.index'))->assertOk()->assertInertia(fn (Assert $page) => $page->has('activities.data', 1));
        $this->get(route('admin.activities.show', $activity))->assertOk()->assertInertia(fn (Assert $page) => $page->has('activity.instructors', 1));
        $this->get(route('admin.activities.create'))->assertOk();
        $this->get(route('admin.activities.edit', $activity))->assertOk();

        People::create(['first_name' => 'Buscable', 'paternal_surname' => 'Persona']);
        $this->getJson(route('admin.people.search', ['q' => 'buscable']))->assertJsonCount(1);
    }
}
