<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClinicRequest;
use App\Models\Clinic;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClinicController extends Controller
{
    public function index(Request $request): Response
    {
        $clinics = Clinic::query()
            ->with('department:id,name')
            ->withCount('spaces')
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/clinics/Index', [
            'clinics' => $clinics,
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/clinics/Create', $this->formData());
    }

    public function store(ClinicRequest $request): RedirectResponse
    {
        $clinic = Clinic::create($request->validated());

        return redirect()
            ->route('admin.clinics.show', $clinic)
            ->with('success', 'Clínica registrada correctamente.');
    }

    public function show(Clinic $clinic): Response
    {
        $clinic->load(['department:id,name', 'spaces']);

        return Inertia::render('admin/clinics/Show', [
            'clinic' => $clinic,
        ]);
    }

    public function edit(Clinic $clinic): Response
    {
        return Inertia::render('admin/clinics/Edit', [
            'clinic' => $clinic,
            ...$this->formData(),
        ]);
    }

    public function update(ClinicRequest $request, Clinic $clinic): RedirectResponse
    {
        $clinic->update($request->validated());

        return redirect()
            ->route('admin.clinics.show', $clinic)
            ->with('success', 'Clínica actualizada correctamente.');
    }

    public function destroy(Clinic $clinic): RedirectResponse
    {
        $clinic->delete();

        return redirect()
            ->route('admin.clinics.index')
            ->with('success', 'Clínica eliminada correctamente.');
    }

    private function formData(): array
    {
        return [
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ];
    }
}
