<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\FeeConcept;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Conceptos de cobro y sus cuotas.
 */
class FeeConceptController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/finance/Concepts', [
            'concepts' => FeeConcept::withCount('charges')->orderBy('category')->orderBy('name')->get(),
            'categories' => FeeConcept::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        FeeConcept::create($this->validated($request));

        return back()->with('success', 'Concepto registrado.');
    }

    public function update(Request $request, FeeConcept $concept): RedirectResponse
    {
        $concept->update($this->validated($request, $concept));

        return back()->with('success', 'Concepto actualizado.');
    }

    public function destroy(FeeConcept $concept): RedirectResponse
    {
        if ($concept->charges()->exists()) {
            return back()->with('error', 'El concepto ya tiene adeudos; desactívalo en lugar de eliminarlo.');
        }

        $concept->delete();

        return back()->with('success', 'Concepto eliminado.');
    }

    private function validated(Request $request, ?FeeConcept $concept = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('fee_concepts', 'code')->ignore($concept)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(FeeConcept::CATEGORIES)],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);
    }
}
