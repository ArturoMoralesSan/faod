<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeArea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Catálogo de áreas de conocimiento (CRUD en una sola vista).
 */
class KnowledgeAreaController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/knowledge-areas/Index', [
            'areas' => KnowledgeArea::withCount('teachers')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        KnowledgeArea::create($this->validated($request));

        return back()->with('success', 'Área registrada correctamente.');
    }

    public function update(Request $request, KnowledgeArea $knowledgeArea): RedirectResponse
    {
        $knowledgeArea->update($this->validated($request, $knowledgeArea));

        return back()->with('success', 'Área actualizada correctamente.');
    }

    public function destroy(KnowledgeArea $knowledgeArea): RedirectResponse
    {
        $knowledgeArea->delete();

        return back()->with('success', 'Área eliminada correctamente.');
    }

    private function validated(Request $request, ?KnowledgeArea $area = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('knowledge_areas', 'name')->ignore($area)],
            'is_active' => ['boolean'],
        ]);
    }
}
