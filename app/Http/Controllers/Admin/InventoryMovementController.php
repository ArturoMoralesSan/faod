<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kárdex general y registro de entradas, salidas, ajustes y traspasos.
 */
class InventoryMovementController extends Controller
{
    public const OPERATIONS = ['entrada', 'salida', 'entrega', 'devolucion', 'merma', 'danado', 'ajuste', 'traspaso'];

    public function __construct(private InventoryService $inventory)
    {
    }

    public function index(Request $request): Response
    {
        $movements = InventoryMovement::query()
            ->with(['item:id,code,name,unit', 'warehouse:id,code,name', 'lot:id,lot_number,expires_at', 'user:id,name', 'supplier:id,name', 'student:id,people_id,registration_number', 'student.people:id,first_name,paternal_surname,maternal_surname'])
            ->when($request->inventory_item_id, fn ($q, $id) => $q->where('inventory_item_id', $id))
            ->when($request->warehouse_id, fn ($q, $id) => $q->where('warehouse_id', $id))
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->student_profile_id, fn ($q, $id) => $q->where('student_profile_id', $id))
            ->when($request->date_from, fn ($q, $date) => $q->whereDate('moved_at', '>=', $date))
            ->when($request->date_to, fn ($q, $date) => $q->whereDate('moved_at', '<=', $date))
            ->latest('moved_at')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('admin/inventory/Movements', [
            'movements' => $movements,
            'filters' => $request->only('inventory_item_id', 'warehouse_id', 'type', 'student_profile_id', 'date_from', 'date_to'),
            'students' => InventoryItemController::studentOptions(),
            'types' => InventoryMovement::TYPES,
            'items' => InventoryItem::orderBy('name')->get(['id', 'code', 'name']),
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    /**
     * operation: entrada | salida | entrega | devolucion | merma | danado | ajuste | traspaso
     */
    public function store(Request $request, InventoryItem $inventory): RedirectResponse
    {
        $operation = $request->validate(['operation' => ['required', Rule::in(self::OPERATIONS)]])['operation'];

        $data = $request->validate(match ($operation) {
            'entrada' => [
                'warehouse_id' => ['required', 'exists:warehouses,id'],
                'quantity' => ['required', 'numeric', 'gt:0'],
                'lot_number' => ['nullable', 'string', 'max:50'],
                'expires_at' => [Rule::requiredIf($inventory->tracks_expiration), 'nullable', 'date'],
                'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
                'unit_cost' => ['nullable', 'numeric', 'min:0'],
                'reference' => ['nullable', 'string', 'max:100'],
                'reason' => ['nullable', 'string', 'max:255'],
            ],
            'salida' => [
                'warehouse_id' => ['required', 'exists:warehouses,id'],
                'quantity' => ['required', 'numeric', 'gt:0'],
                'inventory_lot_id' => ['nullable', Rule::exists('inventory_lots', 'id')->where('inventory_item_id', $inventory->id)],
                'reason' => ['required', 'string', 'max:255'],
                'reference' => ['nullable', 'string', 'max:100'],
            ],
            // Entrega de material a un alumno.
            'entrega' => [
                'warehouse_id' => ['required', 'exists:warehouses,id'],
                'quantity' => ['required', 'numeric', 'gt:0'],
                'student_profile_id' => ['required', Rule::exists('student_profiles', 'id')->whereNull('deleted_at')],
                'inventory_lot_id' => ['nullable', Rule::exists('inventory_lots', 'id')->where('inventory_item_id', $inventory->id)],
                'reason' => ['nullable', 'string', 'max:255'],
                'reference' => ['nullable', 'string', 'max:100'],
            ],
            // Devolución al almacén (de un alumno, una clínica o un laboratorio).
            'devolucion' => [
                'warehouse_id' => ['required', 'exists:warehouses,id'],
                'quantity' => ['required', 'numeric', 'gt:0'],
                'inventory_lot_id' => ['nullable', Rule::exists('inventory_lots', 'id')->where('inventory_item_id', $inventory->id)->where('warehouse_id', $request->input('warehouse_id'))],
                'lot_number' => ['nullable', 'string', 'max:50'],
                'expires_at' => [Rule::requiredIf($inventory->tracks_expiration && ! $request->filled('inventory_lot_id')), 'nullable', 'date'],
                'student_profile_id' => ['nullable', Rule::exists('student_profiles', 'id')->whereNull('deleted_at')],
                'reason' => ['required', 'string', 'max:255'],
                'reference' => ['nullable', 'string', 'max:100'],
            ],
            // Pérdida o producto dañado: salida que se reporta aparte.
            'merma', 'danado' => [
                'warehouse_id' => ['required', 'exists:warehouses,id'],
                'quantity' => ['required', 'numeric', 'gt:0'],
                'inventory_lot_id' => ['nullable', Rule::exists('inventory_lots', 'id')->where('inventory_item_id', $inventory->id)],
                'reason' => ['required', 'string', 'max:255'],
                'reference' => ['nullable', 'string', 'max:100'],
            ],
            'ajuste' => [
                'inventory_lot_id' => ['required', Rule::exists('inventory_lots', 'id')->where('inventory_item_id', $inventory->id)],
                'new_quantity' => ['required', 'numeric', 'min:0'],
                'reason' => ['required', 'string', 'max:255'],
            ],
            'traspaso' => [
                'warehouse_id' => ['required', 'exists:warehouses,id'],
                'to_warehouse_id' => ['required', 'different:warehouse_id', 'exists:warehouses,id'],
                'quantity' => ['required', 'numeric', 'gt:0'],
                'reason' => ['nullable', 'string', 'max:255'],
            ],
        }, [
            'expires_at.required' => 'Este artículo requiere fecha de caducidad.',
            'to_warehouse_id.different' => 'Elige un almacén distinto al de origen.',
        ]);

        match ($operation) {
            'entrada' => $this->inventory->receive($inventory, Warehouse::findOrFail($data['warehouse_id']), (float) $data['quantity'], $data),
            'salida', 'entrega', 'merma', 'danado' => $this->inventory->withdraw(
                $inventory,
                Warehouse::findOrFail($data['warehouse_id']),
                (float) $data['quantity'],
                [...$data, 'type' => $operation],
            ),
            'devolucion' => $this->inventory->receive(
                $inventory,
                Warehouse::findOrFail($data['warehouse_id']),
                (float) $data['quantity'],
                [...$data, 'type' => 'devolucion'],
            ),
            'ajuste' => $this->inventory->adjust(InventoryLot::findOrFail($data['inventory_lot_id']), (float) $data['new_quantity'], $data['reason']),
            'traspaso' => $this->inventory->transfer(
                $inventory,
                Warehouse::findOrFail($data['warehouse_id']),
                Warehouse::findOrFail($data['to_warehouse_id']),
                (float) $data['quantity'],
                $data,
            ),
        };

        return back()->with('success', match ($operation) {
            'entrada' => 'Entrada registrada.',
            'salida' => 'Salida registrada.',
            'entrega' => 'Entrega al alumno registrada.',
            'devolucion' => 'Devolución registrada.',
            'merma' => 'Merma registrada.',
            'danado' => 'Producto dañado registrado.',
            'ajuste' => 'Ajuste registrado.',
            'traspaso' => 'Traspaso registrado.',
        });
    }
}
