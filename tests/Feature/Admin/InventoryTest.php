<?php

namespace Tests\Feature\Admin;

use App\Models\InventoryCount;
use App\Models\InventoryItem;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Patient;
use App\Models\People;
use App\Models\TreatmentPlan;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $main;

    private InventoryItem $anesthetic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->create()->assignRole('SuperAdmin'));

        $this->main = Warehouse::firstWhere('code', 'ALM');
        $this->anesthetic = InventoryItem::create([
            'code' => 'ANE-01',
            'name' => 'Lidocaína 2%',
            'unit' => 'cartucho',
            'minimum_stock' => 20,
            'tracks_expiration' => true,
        ]);
    }

    private function move(array $data, ?InventoryItem $item = null)
    {
        return $this->post(route('admin.inventory.movements.store', $item ?? $this->anesthetic), $data);
    }

    public function test_entries_and_fefo_withdrawal(): void
    {
        // Sin caducidad no se acepta para este artículo.
        $this->move(['operation' => 'entrada', 'warehouse_id' => $this->main->id, 'quantity' => 10])
            ->assertSessionHasErrors('expires_at');

        $this->move(['operation' => 'entrada', 'warehouse_id' => $this->main->id, 'quantity' => 10, 'lot_number' => 'B', 'expires_at' => now()->addMonths(6)->toDateString()])
            ->assertSessionHasNoErrors();
        $this->move(['operation' => 'entrada', 'warehouse_id' => $this->main->id, 'quantity' => 5, 'lot_number' => 'A', 'expires_at' => now()->addMonths(2)->toDateString()])
            ->assertSessionHasNoErrors();
        // Mismo lote y caducidad: se suma al lote existente.
        $this->move(['operation' => 'entrada', 'warehouse_id' => $this->main->id, 'quantity' => 5, 'lot_number' => 'A', 'expires_at' => now()->addMonths(2)->toDateString()]);

        $this->assertSame(2, InventoryLot::count());

        // Salida de 12: se toman primero los 10 del lote A (caduca antes) y 2 del B.
        $this->move(['operation' => 'salida', 'warehouse_id' => $this->main->id, 'quantity' => 12, 'reason' => 'Merma'])
            ->assertSessionHasNoErrors();

        $this->assertEquals(0, InventoryLot::firstWhere('lot_number', 'A')->quantity);
        $this->assertEquals(8, InventoryLot::firstWhere('lot_number', 'B')->quantity);
        $this->assertEquals(8, InventoryMovement::latest('id')->first()->balance_after);

        // No hay existencia negativa.
        $this->move(['operation' => 'salida', 'warehouse_id' => $this->main->id, 'quantity' => 9, 'reason' => 'Merma'])
            ->assertSessionHasErrors('quantity');
    }

    public function test_transfer_adjustment_and_alerts(): void
    {
        $clinicWarehouse = Warehouse::create(['code' => 'CL1', 'name' => 'Almacén clínica']);
        $this->move(['operation' => 'entrada', 'warehouse_id' => $this->main->id, 'quantity' => 10, 'lot_number' => 'A', 'expires_at' => now()->addDays(10)->toDateString()]);

        $this->move(['operation' => 'traspaso', 'warehouse_id' => $this->main->id, 'to_warehouse_id' => $clinicWarehouse->id, 'quantity' => 4])
            ->assertSessionHasNoErrors();

        $destination = InventoryLot::where('warehouse_id', $clinicWarehouse->id)->first();
        $this->assertEquals(4, $destination->quantity);
        $this->assertSame('A', $destination->lot_number); // conserva lote y caducidad

        $this->move(['operation' => 'ajuste', 'inventory_lot_id' => $destination->id, 'new_quantity' => 3, 'reason' => 'Faltante'])
            ->assertSessionHasNoErrors();
        $this->assertEquals(3, $destination->fresh()->quantity);

        // 9 en total < mínimo 20, y el lote caduca en menos de 30 días.
        $this->get(route('admin.inventory.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/inventory/Index')
                ->where('alerts.low', 1)
                ->where('alerts.expiring', 2)
                ->where('items.data.0.stock', 9));

        $this->get(route('admin.inventory.show', $this->anesthetic))->assertOk();
        $this->get(route('admin.inventory.movements.index'))->assertOk();
    }

    public function test_physical_count_adjusts_stock(): void
    {
        $this->move(['operation' => 'entrada', 'warehouse_id' => $this->main->id, 'quantity' => 10, 'expires_at' => now()->addYear()->toDateString()]);

        $this->post(route('admin.inventory-counts.store'), ['warehouse_id' => $this->main->id])->assertRedirect();
        $count = InventoryCount::first();
        $line = $count->lines()->first();
        $this->assertEquals(10, $line->system_quantity);

        // Solo un conteo abierto por almacén.
        $this->post(route('admin.inventory-counts.store'), ['warehouse_id' => $this->main->id])->assertSessionHas('error');

        $this->put(route('admin.inventory-counts.update', $count), ['lines' => [['id' => $line->id, 'counted_quantity' => 7]]])
            ->assertSessionHasNoErrors();
        $this->patch(route('admin.inventory-counts.close', $count))->assertSessionHas('success');

        $this->assertSame('cerrado', $count->fresh()->status);
        $this->assertEquals(7, InventoryLot::first()->quantity);
        $this->assertDatabaseHas('inventory_movements', ['type' => 'ajuste', 'inventory_count_id' => $count->id, 'quantity' => -3]);

        $this->get(route('admin.inventory-counts.show', $count))->assertOk();
    }

    public function test_procedure_material_consumes_and_returns_stock(): void
    {
        $this->move(['operation' => 'entrada', 'warehouse_id' => $this->main->id, 'quantity' => 5, 'expires_at' => now()->addYear()->toDateString()]);

        $people = People::create(['first_name' => 'Pac', 'paternal_surname' => 'Uno']);
        $patient = Patient::create(['people_id' => $people->id, 'registered_at' => now()]);
        $plan = TreatmentPlan::create(['patient_id' => $patient->id, 'title' => 'Plan', 'started_at' => now()]);
        $procedure = $plan->procedures()->create(['patient_id' => $patient->id, 'description' => 'Extracción', 'status' => 'planeado']);

        $this->post(route('admin.treatment-procedures.materials.store', $procedure), [
            'inventory_item_id' => $this->anesthetic->id,
            'warehouse_id' => $this->main->id,
            'quantity' => 2,
        ])->assertSessionHasNoErrors();

        $this->assertEquals(3, InventoryLot::first()->quantity);
        $material = $procedure->materials()->first();
        $this->assertSame('Lidocaína 2%', $material->name);
        $this->assertDatabaseHas('inventory_movements', ['type' => 'consumo', 'treatment_procedure_id' => $procedure->id]);

        // Más de lo que hay.
        $this->post(route('admin.treatment-procedures.materials.store', $procedure), [
            'inventory_item_id' => $this->anesthetic->id,
            'warehouse_id' => $this->main->id,
            'quantity' => 10,
        ])->assertSessionHasErrors('quantity');

        $this->delete(route('admin.procedure-materials.destroy', $material))->assertSessionHasNoErrors();
        $this->assertEquals(5, InventoryLot::sum('quantity'));
    }
}
