<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SpaceRequest;
use App\Models\AcademicPeriod;
use App\Models\ClassSchedule;
use App\Models\Clinic;
use App\Models\Space;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Aulas, laboratorios, consultorios y auditorios.
 */
class SpaceController extends Controller
{
    public function index(Request $request): Response
    {
        $spaces = Space::query()
            ->with('clinic:id,name')
            ->when($request->search, fn ($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->clinic_id, fn ($q, $id) => $q->where('clinic_id', $id))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/spaces/Index', [
            'spaces' => $spaces,
            'filters' => $request->only('search', 'type', 'clinic_id'),
            ...$this->formData(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/spaces/Create', $this->formData());
    }

    public function store(SpaceRequest $request): RedirectResponse
    {
        Space::create($request->validated());

        return redirect()
            ->route('admin.spaces.index')
            ->with('success', 'Espacio registrado correctamente.');
    }

    /**
     * Ocupación semanal del espacio en el periodo vigente (o el indicado).
     */
    public function show(Request $request, Space $space): Response
    {
        $periodId = $request->integer('academic_period_id') ?: AcademicPeriod::current()?->id;

        $schedules = ClassSchedule::query()
            ->where('space_id', $space->id)
            ->whereHas('classSection.group', fn ($q) => $q->where('academic_period_id', $periodId))
            ->with([
                'classSection.subject:id,code,name',
                'classSection.group:id,code',
                'classSection.teacher.people:id,first_name,paternal_surname',
            ])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return Inertia::render('admin/spaces/Show', [
            'space' => $space->load('clinic:id,name'),
            'schedules' => $schedules,
            'days' => ClassSchedule::DAYS,
            'periods' => AcademicPeriod::orderByDesc('starts_at')->get(['id', 'name']),
            'academicPeriodId' => $periodId,
        ]);
    }

    public function edit(Space $space): Response
    {
        return Inertia::render('admin/spaces/Edit', [
            'space' => $space,
            ...$this->formData(),
        ]);
    }

    public function update(SpaceRequest $request, Space $space): RedirectResponse
    {
        $space->update($request->validated());

        return redirect()
            ->route('admin.spaces.index')
            ->with('success', 'Espacio actualizado correctamente.');
    }

    public function destroy(Space $space): RedirectResponse
    {
        $space->delete();

        return redirect()
            ->route('admin.spaces.index')
            ->with('success', 'Espacio eliminado correctamente.');
    }

    private function formData(): array
    {
        return [
            'types' => Space::TYPES,
            'clinics' => Clinic::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ];
    }
}
