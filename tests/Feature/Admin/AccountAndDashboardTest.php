<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create()->assignRole('SuperAdmin');
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_records_last_access(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_deactivated_user_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->actingAs($this->admin)
            ->patch(route('admin.users.toggle-active', $user))
            ->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->is_active);

        $this->actingAs($user->fresh())
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.users.toggle-active', $this->admin))
            ->assertSessionHas('error');

        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_admin_can_send_password_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.users.password-reset', $user))
            ->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_dashboard_shows_stats_by_permission(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('stats', 12) // Sin periodo vigente no se muestra "Grupos del periodo".
                ->whereNot('todaySessions', null));

        // Un alumno ve pacientes y citas, pero no las listas de clínicas ni de cuentas.
        $student = User::factory()->create()->assignRole('alumno');

        $this->actingAs($student)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('stats', 2)
                ->whereNot('todayAppointments', null)
                ->where('todaySessions', null)
                ->where('openIncidents', null));
    }
}
