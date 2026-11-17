<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Procedure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Catálogo de procedimientos (CRUD en una sola vista).
 */
class ProcedureController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('admin/procedures/Index', [
            'procedures' => Procedure::query()
                ->withCount('treatmentProcedures')
                ->when($request->search, fn ($q, $search) => $q->where(fn ($q) => $q
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")))
                ->orderBy('category')
                ->orderBy('code')
                ->get(),
            'categories' => Procedure::whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Procedure::create($this->validated($request));

        return back()->with('success', 'Procedimiento agregado al catálogo.');
    }

    public function update(Request $request, Procedure $procedure): RedirectResponse
    {
        $procedure->update($this->validated($request, $procedure));

        return back()->with('success', 'Procedimiento actualizado.');
    }

    public function destroy(Procedure $procedure): RedirectResponse
    {
        if ($procedure->treatmentProcedures()->exists()) {
            return back()->with('error', 'El procedimiento ya se usó en planes de tratamiento; desactívalo en lugar de eliminarlo.');
        }

        $procedure->delete();

        return back()->with('success', 'Procedimiento eliminado del catálogo.');
    }

    private function validated(Request $request, ?Procedure $procedure = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('procedures', 'code')->ignore($procedure)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:60'],
            'default_cost' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);
    }
}
