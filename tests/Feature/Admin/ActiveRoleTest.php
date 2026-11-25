<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * El rol elegido en el selector restringe las rutas, no solo el menú.
 */
class ActiveRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function switchTo(User $user, string $role): void
    {
        $this->actingAs($user)
            ->put(route('admin.active-role.update'), ['role_id' => Role::findByName($role)->id])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_routes_follow_the_active_role(): void
    {
        $user = User::factory()->create()->assignRole('docente', 'admin');

        $this->switchTo($user, 'docente');
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.groups.index'))->assertOk();
        $this->assertFalse($user->fresh()->can('users.view'));

        $this->switchTo($user, 'admin');
        $this->get(route('admin.users.index'))->assertOk();
    }

    public function test_super_admin_only_applies_when_active(): void
    {
        $user = User::factory()->create()->assignRole('SuperAdmin', 'alumno');

        $this->switchTo($user, 'alumno');
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.patients.index'))->assertOk();

        $this->switchTo($user, 'SuperAdmin');
        $this->get(route('admin.users.index'))->assertOk();
    }

    public function test_cannot_switch_to_a_role_the_user_does_not_have(): void
    {
        $user = User::factory()->create()->assignRole('alumno');

        $this->actingAs($user)
            ->put(route('admin.active-role.update'), ['role_id' => Role::findByName('admin')->id])
            ->assertForbidden();

        $this->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_resource_routes_require_only_the_action_permission(): void
    {
        // El alumno ve y edita pacientes pero no puede eliminarlos.
        $user = User::factory()->create()->assignRole('alumno');
        $patient = \App\Models\Patient::create([
            'people_id' => \App\Models\People::create(['first_name' => 'Rosa', 'paternal_surname' => 'Díaz'])->id,
            'registered_at' => now(),
        ]);

        $this->actingAs($user);
        $this->get(route('admin.patients.index'))->assertOk();
        $this->get(route('admin.patients.show', $patient))->assertOk();
        $this->delete(route('admin.patients.destroy', $patient))->assertForbidden();
        $this->get(route('admin.users.show', $user))->assertForbidden();
    }

    public function test_attachments_require_the_module_permission(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $expense = \App\Models\Expense::create(['category' => 'papeleria', 'description' => 'Papelería', 'amount' => 100, 'method' => 'efectivo', 'paid_at' => today()]);
        $attachment = $expense->attachments()->create([
            'title' => 'Factura', 'disk' => 'local', 'path' => 'x/factura.pdf', 'original_name' => 'factura.pdf',
            'mime_type' => 'application/pdf', 'size' => 10,
        ]);
        \Illuminate\Support\Facades\Storage::disk('local')->put('x/factura.pdf', 'pdf');

        $this->actingAs(User::factory()->create()->assignRole('alumno'));
        $this->get(route('admin.attachments.download', $attachment))->assertForbidden();
        $this->delete(route('admin.attachments.destroy', $attachment))->assertForbidden();

        $this->actingAs(User::factory()->create()->assignRole('admin'));
        $this->get(route('admin.attachments.download', $attachment))->assertOk();
    }

    public function test_direct_permissions_apply_with_any_role(): void
    {
        $user = User::factory()->create()->assignRole('alumno', 'docente');
        $user->givePermissionTo('users.view');

        $this->switchTo($user, 'alumno');
        $this->get(route('admin.users.index'))->assertOk();
    }
}
