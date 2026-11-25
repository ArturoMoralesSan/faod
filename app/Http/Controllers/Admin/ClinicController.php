<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClinicRequest;
use App\Models\Clinic;
use App\Models\ClinicEquipment;
use App\Models\ClinicIncident;
use App\Models\ClinicSupervisor;
use App\Models\Department;
use App\Models\Specialty;
use App\Models\TeacherProfile;
use App\Support\TeacherScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClinicController extends Controller
{
    public function index(Request $request): Response
    {
        $clinics = Clinic::query()
            ->tap(fn ($q) => TeacherScope::apply($q, TeacherScope::teacherId($request->user())))
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

    /**
     * Clínica con áreas, consultorios, supervisores, equipos,
     * próximas sesiones e incidencias abiertas.
     */
    public function show(Clinic $clinic): Response
    {
        TeacherScope::ensureVisible($clinic, request()->user());

        $clinic->load([
            'department:id,name',
            'areas.specialty:id,name',
            'spaces' => fn ($q) => $q->with('area:id,name')->orderBy('name'),
            'supervisors' => fn ($q) => $q
                ->with(['teacher.people:id,first_name,paternal_surname,maternal_surname', 'area:id,name'])
                ->orderBy('role'),
            'equipment.space:id,code,name',
        ]);

        return Inertia::render('admin/clinics/Show', [
            'clinic' => $clinic,
            'upcomingSessions' => $clinic->clinicalSessions()
                ->whereDate('date', '>=', today())
                ->where('status', '!=', 'cancelada')
                ->with(['space:id,code,name', 'group:id,code', 'supervisor.people:id,first_name,paternal_surname'])
                ->orderBy('date')
                ->orderBy('start_time')
                ->limit(8)
                ->get(),
            'openIncidents' => $clinic->incidents()
                ->whereNotIn('status', ClinicIncident::CLOSED_STATUSES)
                ->latest('occurred_at')
                ->get(['id', 'clinic_id', 'title', 'type', 'severity', 'status', 'occurred_at']),
            'teachers' => TeacherProfile::where('status', 'active')
                ->with('people:id,first_name,paternal_surname,maternal_surname')
                ->get(['id', 'people_id']),
            'supervisorRoles' => ClinicSupervisor::ROLES,
            'equipmentStatuses' => ClinicEquipment::STATUSES,
            'equipmentKinds' => ClinicEquipment::KINDS,
            'specialties' => Specialty::where('is_active', true)->orderBy('name')->get(['id', 'name']),
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
