<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SpecialtyRequest;
use App\Models\Specialty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SpecialtyController extends Controller
{
    public function index(Request $request): Response
    {
        $specialties = Specialty::query()
            ->withCount(['academicPrograms', 'teachers'])
            ->when($request->search, fn ($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/specialties/Index', [
            'specialties' => $specialties,
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/specialties/Create');
    }

    public function store(SpecialtyRequest $request): RedirectResponse
    {
        Specialty::create($request->validated());

        return redirect()
            ->route('admin.specialties.index')
            ->with('success', 'Especialidad registrada correctamente.');
    }

    public function edit(Specialty $specialty): Response
    {
        return Inertia::render('admin/specialties/Edit', [
            'specialty' => $specialty,
        ]);
    }

    public function update(SpecialtyRequest $request, Specialty $specialty): RedirectResponse
    {
        $specialty->update($request->validated());

        return redirect()
            ->route('admin.specialties.index')
            ->with('success', 'Especialidad actualizada correctamente.');
    }

    public function destroy(Specialty $specialty): RedirectResponse
    {
        $specialty->delete();

        return redirect()
            ->route('admin.specialties.index')
            ->with('success', 'Especialidad eliminada correctamente.');
    }
}
