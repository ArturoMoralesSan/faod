<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AcademicPeriodRequest;
use App\Models\AcademicPeriod;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AcademicPeriodController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/academic-periods/Index', [
            'periods' => AcademicPeriod::withCount('groups')
                ->orderByDesc('starts_at')
                ->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/academic-periods/Create');
    }

    public function store(AcademicPeriodRequest $request): RedirectResponse
    {
        AcademicPeriod::create($request->validated());

        return redirect()
            ->route('admin.academic-periods.index')
            ->with('success', 'Periodo registrado correctamente.');
    }

    public function edit(AcademicPeriod $academicPeriod): Response
    {
        return Inertia::render('admin/academic-periods/Edit', [
            'period' => $academicPeriod,
        ]);
    }

    public function update(AcademicPeriodRequest $request, AcademicPeriod $academicPeriod): RedirectResponse
    {
        $academicPeriod->update($request->validated());

        return redirect()
            ->route('admin.academic-periods.index')
            ->with('success', 'Periodo actualizado correctamente.');
    }

    public function destroy(AcademicPeriod $academicPeriod): RedirectResponse
    {
        $academicPeriod->delete();

        return redirect()
            ->route('admin.academic-periods.index')
            ->with('success', 'Periodo eliminado correctamente.');
    }
}
