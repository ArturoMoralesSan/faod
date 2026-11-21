<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Space;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Almacenes (general, de cada clínica y de cada laboratorio).
 */
class WarehouseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/warehouses/Index', [
            'warehouses' => Warehouse::query()
                ->with(['clinic:id,name', 'space:id,code,name'])
                ->withCount(['lots as items_count' => fn ($q) => $q->where('quantity', '>', 0)])
                ->orderBy('name')
                ->get(),
            'clinics' => Clinic::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'laboratories' => Space::where('type', 'laboratorio')->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Warehouse::create($this->validated($request));

        return back()->with('success', 'Almacén registrado.');
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $warehouse->update($this->validated($request, $warehouse));

        return back()->with('success', 'Almacén actualizado.');
    }

    private function validated(Request $request, ?Warehouse $warehouse = null): array
    {
        return $request->validate([
            'clinic_id' => ['nullable', Rule::exists('clinics', 'id')->whereNull('deleted_at')],
            'space_id' => ['nullable', Rule::exists('spaces', 'id')->where('type', 'laboratorio')->whereNull('deleted_at')],
            'code' => ['required', 'string', 'max:20', Rule::unique('warehouses', 'code')->ignore($warehouse)],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);
    }
}
