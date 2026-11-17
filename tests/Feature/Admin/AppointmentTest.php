<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\People;
use App\Models\Space;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    private Clinic $clinic;

    private Space $space;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->create()->assignRole('SuperAdmin'));

        $people = People::create(['first_name' => 'Elena', 'paternal_surname' => 'Citas', 'personal_email' => 'elena@example.com']);
        $this->patient = Patient::create(['people_id' => $people->id, 'registered_at' => now()]);
        $this->clinic = Clinic::create(['code' => 'CL', 'name' => 'Clínica']);
        $this->space = Space::create(['clinic_id' => $this->clinic->id, 'type' => 'consultorio', 'code' => 'C1', 'name' => 'Consultorio 1']);
    }

    private function data(array $overrides = []): array
    {
        return [
            'patient_id' => $this->patient->id,
            'clinic_id' => $this->clinic->id,
            'space_id' => $this->space->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            ...$overrides,
        ];
    }

    public function test_appointment_conflicts(): void
    {
        $this->post(route('admin.appointments.store'), $this->data())->assertSessionHasNoErrors();

        // Mismo consultorio y horario encimado (y el mismo paciente).
        $this->post(route('admin.appointments.store'), $this->data(['start_time' => '09:30', 'end_time' => '10:30']))
            ->assertSessionHasErrors(['space_id', 'start_time']);

        // Contigua sí.
        $this->post(route('admin.appointments.store'), $this->data(['start_time' => '10:00', 'end_time' => '11:00']))
            ->assertSessionHasNoErrors();

        // Fecha pasada.
        $this->post(route('admin.appointments.store'), $this->data(['date' => now()->subDay()->toDateString()]))
            ->assertSessionHasErrors('date');

        // Fuera del horario de atención del consultorio.
        $this->space->availabilities()->create(['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '12:00']);
        $this->post(route('admin.appointments.store'), $this->data([
            'date' => now()->next('Tuesday')->toDateString(),
            'start_time' => '15:00',
            'end_time' => '16:00',
        ]))->assertSessionHasErrors('start_time');
    }

    public function test_status_actions_and_reschedule(): void
    {
        $this->post(route('admin.appointments.store'), $this->data());
        $appointment = Appointment::first();

        $this->patch(route('admin.appointments.confirm', $appointment))->assertSessionHas('success');
        $this->assertSame('confirmada', $appointment->fresh()->status);

        // Cancelar exige motivo.
        $this->patch(route('admin.appointments.cancel', $appointment))->assertSessionHasErrors('cancellation_reason');

        // Reprogramar: nueva cita y la original queda reprogramada; no choca consigo misma.
        $this->post(route('admin.appointments.reschedule', $appointment), $this->data(['start_time' => '09:30', 'end_time' => '10:30']))
            ->assertSessionHasNoErrors();

        $this->assertSame('reprogramada', $appointment->fresh()->status);
        $new = Appointment::latest('id')->first();
        $this->assertSame($appointment->id, $new->rescheduled_from_id);
        $this->assertSame('programada', $new->status);

        // Una cita cerrada no se puede confirmar.
        $this->patch(route('admin.appointments.confirm', $appointment))->assertSessionHas('error');

        $this->patch(route('admin.appointments.no-show', $new))->assertSessionHas('success');
        $this->assertSame('no_asistio', $new->fresh()->status);
    }

    public function test_reminders(): void
    {
        Notification::fake();

        $this->post(route('admin.appointments.store'), $this->data());
        $appointment = Appointment::first();

        $this->artisan('appointments:send-reminders')->assertSuccessful();

        Notification::assertSentTo(new AnonymousNotifiable, AppointmentReminder::class);
        $this->assertNotNull($appointment->fresh()->reminder_sent_at);

        // Ya enviado: no se repite.
        Notification::fake();
        $this->artisan('appointments:send-reminders');
        Notification::assertNothingSent();
    }

    public function test_agenda_pages_and_patient_search(): void
    {
        $this->post(route('admin.appointments.store'), $this->data());

        $this->get(route('admin.appointments.index', ['date' => now()->addDay()->toDateString()]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/appointments/Index')->has('appointments', 1));

        $this->get(route('admin.appointments.index', ['view' => 'list']))
            ->assertInertia(fn (Assert $page) => $page->has('appointments.data', 1));

        $this->get(route('admin.appointments.create', ['patient_id' => $this->patient->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('defaults.patient_id', $this->patient->id));

        $this->get(route('admin.appointments.edit', Appointment::first()))->assertOk();

        $this->getJson(route('admin.patients.search', ['q' => 'elena']))
            ->assertOk()
            ->assertJsonPath('0.id', $this->patient->id);
    }
}
