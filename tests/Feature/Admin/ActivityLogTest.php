<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create(['name' => 'Admin Pruebas'])->assignRole('SuperAdmin');
        $this->actingAs($this->admin);
    }

    private function logsFor(string $class, int $id)
    {
        return ActivityLog::where('subject_type', $class)->where('subject_id', $id)->orderBy('id')->get();
    }

    public function test_records_create_update_delete_and_restore_with_user_and_changes(): void
    {
        $item = \App\Models\InventoryItem::create(['code' => 'GUA-01', 'name' => 'Guantes', 'unit' => 'caja', 'minimum_stock' => 5]);

        $item->update(['name' => 'Guantes de nitrilo', 'minimum_stock' => 10]);
        // Guardar sin cambios no deja registro.
        $item->save();
        $item->delete();
        $item->restore();

        $logs = $this->logsFor(\App\Models\InventoryItem::class, $item->id);

        $this->assertSame(['created', 'updated', 'deleted', 'restored'], $logs->pluck('event')->all());
        $this->assertTrue($logs->every(fn ($log) => $log->user_id === $this->admin->id));

        $created = $logs[0];
        $this->assertSame([null, 'Guantes'], $created->changes['name']);
        $this->assertArrayNotHasKey('created_at', $created->changes);
        $this->assertSame('Guantes', $created->subject_label);

        $updated = $logs[1];
        $this->assertSame(['name', 'minimum_stock'], array_keys($updated->changes));
        $this->assertSame(['Guantes', 'Guantes de nitrilo'], $updated->changes['name']);
        $this->assertSame('Guantes de nitrilo', $updated->subject_label);

        $this->assertNull($logs[2]->changes);
    }

    public function test_sensitive_fields_are_not_logged(): void
    {
        $user = User::factory()->create(['name' => 'Ana']);
        $user->update(['password' => 'otra-clave-segura', 'last_login_at' => now()]);

        $logs = $this->logsFor(User::class, $user->id);

        $this->assertCount(1, $logs, 'Cambiar solo contraseña o último acceso no debe registrarse.');
        $this->assertArrayNotHasKey('password', $logs[0]->changes);
        $this->assertArrayNotHasKey('remember_token', $logs[0]->changes);
        $this->assertSame([null, 'Ana'], $logs[0]->changes['name']);
    }

    public function test_models_without_trait_are_not_logged(): void
    {
        $before = ActivityLog::count();

        Supplier::create(['name' => 'Dental Norte']);
        Warehouse::query()->first()?->touch();

        $this->assertSame($before, ActivityLog::count());
    }

    public function test_index_lists_and_filters(): void
    {
        $item = \App\Models\InventoryItem::create(['code' => 'RES-01', 'name' => 'Resina', 'unit' => 'jeringa']);
        $item->update(['name' => 'Resina A2']);
        User::factory()->create(['name' => 'Beto']);

        $this->get(route('admin.activity-log.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/activity-log/Index')
                ->where('logs.total', ActivityLog::count())
                ->where('users.0.name', 'Admin Pruebas'));

        $this->get(route('admin.activity-log.index', ['subject_type' => 'InventoryItem', 'event' => 'updated']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('logs.total', 1)
                ->where('logs.data.0.subject_name', 'Artículo de inventario')
                ->where('logs.data.0.user.name', 'Admin Pruebas')
                ->where('logs.data.0.changes.name', ['Resina', 'Resina A2']));

        $this->get(route('admin.activity-log.index', ['subject_type' => 'InventoryItem', 'subject_id' => $item->id]))
            ->assertInertia(fn (Assert $page) => $page->where('logs.total', 2));

        $this->get(route('admin.activity-log.index', ['search' => 'Beto']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('logs.total', 1)
                ->where('logs.data.0.subject_type', 'User'));
    }

    public function test_requires_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.activity-log.index'))->assertForbidden();
    }
}
