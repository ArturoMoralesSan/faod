<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Diagnosis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Catálogo de diagnósticos (CRUD en una sola vista).
 */
class DiagnosisController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('admin/diagnoses/Index', [
            'diagnoses' => Diagnosis::query()
                ->withCount('patientDiagnoses')
                ->when($request->search, fn ($q, $search) => $q->where(fn ($q) => $q
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")))
                ->orderBy('code')
                ->get(),
            'categories' => Diagnosis::whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Diagnosis::create($this->validated($request));

        return back()->with('success', 'Diagnóstico agregado al catálogo.');
    }

    public function update(Request $request, Diagnosis $diagnosis): RedirectResponse
    {
        $diagnosis->update($this->validated($request, $diagnosis));

        return back()->with('success', 'Diagnóstico actualizado.');
    }

    public function destroy(Diagnosis $diagnosis): RedirectResponse
    {
        if ($diagnosis->patientDiagnoses()->exists()) {
            return back()->with('error', 'El diagnóstico ya se usó en expedientes; desactívalo en lugar de eliminarlo.');
        }

        $diagnosis->delete();

        return back()->with('success', 'Diagnóstico eliminado del catálogo.');
    }

    private function validated(Request $request, ?Diagnosis $diagnosis = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('diagnoses', 'code')->ignore($diagnosis)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);
    }
}
