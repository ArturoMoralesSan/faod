<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\CatalogType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Opciones de un catálogo (se administran desde la vista del catálogo).
 */
class CatalogItemController extends Controller
{
    public function store(Request $request, CatalogType $catalog): RedirectResponse
    {
        $catalog->items()->create($this->validated($request, $catalog));

        return back()->with('success', 'Opción agregada correctamente.');
    }

    public function update(Request $request, CatalogItem $item): RedirectResponse
    {
        $item->update($this->validated($request, $item->catalogType, $item));

        return back()->with('success', 'Opción actualizada correctamente.');
    }

    public function destroy(CatalogItem $item): RedirectResponse
    {
        $item->delete();

        return back()->with('success', 'Opción eliminada correctamente.');
    }

    private function validated(Request $request, CatalogType $catalog, ?CatalogItem $item = null): array
    {
        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('catalog_items', 'code')
                    ->where('catalog_type_id', $catalog->id)
                    ->ignore($item),
            ],
            'name' => ['required', 'string', 'max:255'],
            'meta' => ['nullable', 'array'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);
    }
}
