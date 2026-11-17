<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Artículos de inventario con su existencia y alertas.
 */
class InventoryItemController extends Controller
{
    public const EXPIRATION_ALERT_DAYS = 30;

    public function index(Request $request): Response
    {
        $warehouseId = $request->integer('warehouse_id') ?: null;

        $items = InventoryItem::query()
            ->withStock($warehouseId)
            ->with('defaultSupplier:id,name')
            ->withCount(['lots as expiring_lots_count' => fn ($q) => $q
                ->expiringWithin(self::EXPIRATION_ALERT_DAYS)
                ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))])
            ->when($request->search, fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")))
            ->when($request->category, fn ($q, $category) => $q->where('category', $category))
            ->when($request->alert === 'low', fn ($q) => $q->lowStock())
            ->when($request->alert === 'expiring', fn ($q) => $q->whereHas('lots', fn ($q) => $q->expiringWithin(self::EXPIRATION_ALERT_DAYS)))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/inventory/Index', [
            'items' => $items,
            'filters' => $request->only('search', 'category', 'warehouse_id', 'alert'),
            'alerts' => [
                'low' => InventoryItem::lowStock()->count(),
                'expiring' => InventoryLot::expiringWithin(self::EXPIRATION_ALERT_DAYS)->count(),
                'expired' => InventoryLot::where('quantity', '>', 0)->whereDate('expires_at', '<', today())->count(),
            ],
            ...$this->formData(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $item = InventoryItem::create($this->validated($request));

        return redirect()
            ->route('admin.inventory.show', $item)
            ->with('success', 'Artículo registrado. Registra su primera entrada para tener existencia.');
    }

    public function show(InventoryItem $inventory): Response
    {
        $inventory->load('defaultSupplier:id,name');

        return Inertia::render('admin/inventory/Show', [
            'item' => $inventory,
            'stock' => (float) $inventory->lots()->sum('quantity'),
            'lots' => $inventory->lots()
                ->with(['warehouse:id,code,name', 'supplier:id,name'])
                ->where('quantity', '>', 0)
                ->orderByRaw('expires_at is null')
                ->orderBy('expires_at')
                ->get(),
            'movements' => InventoryMovement::query()
                ->where('inventory_item_id', $inventory->id)
                ->with(['warehouse:id,code,name', 'lot:id,lot_number,expires_at', 'user:id,name', 'supplier:id,name'])
                ->latest('moved_at')
                ->latest('id')
                ->limit(50)
                ->get(),
            'expirationAlertDays' => self::EXPIRATION_ALERT_DAYS,
            ...$this->formData(),
        ]);
    }

    public function update(Request $request, InventoryItem $inventory): RedirectResponse
    {
        $inventory->update($this->validated($request, $inventory));

        return back()->with('success', 'Artículo actualizado.');
    }

    public function destroy(InventoryItem $inventory): RedirectResponse
    {
        if ($inventory->lots()->where('quantity', '>', 0)->exists()) {
            return back()->with('error', 'El artículo tiene existencia; dale salida o desactívalo.');
        }

        $inventory->delete();

        return redirect()->route('admin.inventory.index')->with('success', 'Artículo eliminado.');
    }

    private function validated(Request $request, ?InventoryItem $item = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('inventory_items', 'code')->ignore($item)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:60'],
            'unit' => ['required', 'string', 'max:20'],
            'minimum_stock' => ['required', 'numeric', 'min:0'],
            'tracks_expiration' => ['boolean'],
            'default_supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);
    }

    private function formData(): array
    {
        return [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => InventoryItem::whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
        ];
    }
}
