<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Diagnosis;
use App\Models\Specialty;
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
                ->with('specialty:id,name')
                ->when($request->integer('specialty_id'), fn ($q, $id) => $q->where('specialty_id', $id))
                ->when($request->search, fn ($q, $search) => $q->where(fn ($q) => $q
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")))
                ->orderBy('code')
                ->get(),
            'categories' => Diagnosis::whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'filters' => $request->only('search', 'specialty_id'),
            'specialties' => Specialty::where('is_active', true)->orderBy('name')->get(['id', 'name']),
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
            'specialty_id' => ['nullable', Rule::exists('specialties', 'id')],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);
    }
}
