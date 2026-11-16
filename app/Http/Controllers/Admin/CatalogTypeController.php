<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CatalogTypeRequest;
use App\Models\CatalogType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogTypeController extends Controller
{
    public function index(Request $request): Response
    {
        $catalogs = CatalogType::query()
            ->withCount('items')
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/catalogs/Index', [
            'catalogs' => $catalogs,
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/catalogs/Create');
    }

    public function store(CatalogTypeRequest $request): RedirectResponse
    {
        $catalog = CatalogType::create($request->validated());

        return redirect()
            ->route('admin.catalogs.show', $catalog)
            ->with('success', 'Catálogo registrado correctamente.');
    }

    public function show(CatalogType $catalog): Response
    {
        $catalog->load('items');

        return Inertia::render('admin/catalogs/Show', [
            'catalog' => $catalog,
        ]);
    }

    public function edit(CatalogType $catalog): Response
    {
        return Inertia::render('admin/catalogs/Edit', [
            'catalog' => $catalog,
        ]);
    }

    public function update(CatalogTypeRequest $request, CatalogType $catalog): RedirectResponse
    {
        $data = $request->validated();

        // El código de un catálogo del sistema no se puede cambiar.
        if ($catalog->is_system) {
            unset($data['code']);
        }

        $catalog->update($data);

        return redirect()
            ->route('admin.catalogs.show', $catalog)
            ->with('success', 'Catálogo actualizado correctamente.');
    }

    public function destroy(CatalogType $catalog): RedirectResponse
    {
        if ($catalog->is_system) {
            return back()->with('error', 'Los catálogos del sistema no se pueden eliminar.');
        }

        $catalog->delete();

        return redirect()
            ->route('admin.catalogs.index')
            ->with('success', 'Catálogo eliminado correctamente.');
    }
}
