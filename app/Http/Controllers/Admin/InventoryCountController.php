<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryCount;
use App\Models\InventoryLot;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Inventario físico: se abre un conteo con las existencias del sistema,
 * se captura lo contado y al cerrarlo se generan los ajustes.
 */
class InventoryCountController extends Controller
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public function index(): Response
    {
        return Inertia::render('admin/inventory/Counts', [
            'counts' => InventoryCount::query()
                ->with(['warehouse:id,code,name', 'user:id,name', 'closer:id,name'])
                ->withCount('lines')
                ->latest('id')
                ->paginate(20),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string'],
        ]);

        if (InventoryCount::where('warehouse_id', $data['warehouse_id'])->where('status', 'abierto')->exists()) {
            return back()->with('error', 'Ese almacén ya tiene un conteo abierto.');
        }

        $count = DB::transaction(function () use ($data, $request) {
            $count = InventoryCount::create([...$data, 'status' => 'abierto', 'user_id' => $request->user()->id]);

            // Foto de las existencias actuales del almacén.
            InventoryLot::query()
                ->where('warehouse_id', $data['warehouse_id'])
                ->where('quantity', '>', 0)
                ->get()
                ->each(fn (InventoryLot $lot) => $count->lines()->create([
                    'inventory_lot_id' => $lot->id,
                    'system_quantity' => $lot->quantity,
                ]));

            return $count;
        });

        return redirect()->route('admin.inventory-counts.show', $count)->with('success', 'Conteo abierto.');
    }

    public function show(InventoryCount $inventoryCount): Response
    {
        $inventoryCount->load([
            'warehouse:id,code,name',
            'user:id,name',
            'closer:id,name',
            'lines.lot:id,inventory_item_id,lot_number,expires_at',
            'lines.lot.item:id,code,name,unit',
        ]);

        return Inertia::render('admin/inventory/CountShow', [
            'count' => $inventoryCount,
        ]);
    }

    /** Guarda lo contado (se puede hacer en varias vueltas). */
    public function update(Request $request, InventoryCount $inventoryCount): RedirectResponse
    {
        abort_if($inventoryCount->status !== 'abierto', 422, 'El conteo ya está cerrado.');

        $data = $request->validate([
            'lines' => ['required', 'array'],
            'lines.*.id' => ['required', 'integer'],
            'lines.*.counted_quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach ($data['lines'] as $line) {
            $inventoryCount->lines()->whereKey($line['id'])->update(['counted_quantity' => $line['counted_quantity']]);
        }

        return back()->with('success', 'Conteo guardado.');
    }

    /**
     * Cierra el conteo y ajusta cada lote contado a lo que se encontró.
     * Las líneas sin capturar no se modifican.
     */
    public function close(Request $request, InventoryCount $inventoryCount): RedirectResponse
    {
        if ($inventoryCount->status !== 'abierto') {
            return back()->with('error', 'El conteo ya está cerrado.');
        }

        DB::transaction(function () use ($request, $inventoryCount) {
            foreach ($inventoryCount->lines()->with('lot')->whereNotNull('counted_quantity')->get() as $line) {
                $this->inventory->adjust($line->lot, $line->counted_quantity, "Conteo físico #{$inventoryCount->id}", $inventoryCount->id);
            }

            $inventoryCount->update([
                'status' => 'cerrado',
                'closed_at' => now(),
                'closed_by' => $request->user()->id,
            ]);
        });

        return back()->with('success', 'Conteo cerrado y existencias ajustadas.');
    }
}
