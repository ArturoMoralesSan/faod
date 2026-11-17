<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClinicIncidentRequest;
use App\Models\Clinic;
use App\Models\ClinicalSession;
use App\Models\ClinicEquipment;
use App\Models\ClinicIncident;
use App\Models\Space;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Incidencias de las clínicas (fallas de equipo, instalaciones, bioseguridad, etc.).
 */
class ClinicIncidentController extends Controller
{
    public function index(Request $request): Response
    {
        $incidents = ClinicIncident::query()
            ->with([
                'clinic:id,name',
                'space:id,code,name',
                'equipment:id,code,name',
                'reporter:id,name',
            ])
            ->when($request->search, fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->when($request->clinic_id, fn ($q, $id) => $q->where('clinic_id', $id))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->severity, fn ($q, $severity) => $q->where('severity', $severity))
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            // Primero las abiertas y más graves.
            ->orderByRaw($this->orderByList('status', ClinicIncident::STATUSES))
            ->orderByRaw($this->orderByList('severity', array_reverse(ClinicIncident::SEVERITIES)))
            ->latest('occurred_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/clinic-incidents/Index', [
            'incidents' => $incidents,
            'filters' => $request->only('search', 'clinic_id', 'status', 'severity', 'type'),
            'clinics' => Clinic::orderBy('name')->get(['id', 'name']),
            ...$this->enums(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('admin/clinic-incidents/Create', [
            ...$this->formData(),
            'defaults' => $request->only('clinic_id', 'space_id', 'clinic_equipment_id', 'clinical_session_id'),
        ]);
    }

    public function store(ClinicIncidentRequest $request): RedirectResponse
    {
        $incident = new ClinicIncident($request->validated());
        $incident->reported_by = $request->user()->id;
        $this->syncResolution($incident, $request);
        $incident->save();

        return redirect()
            ->route('admin.clinic-incidents.show', $incident)
            ->with('success', 'Incidencia registrada correctamente.');
    }

    public function show(ClinicIncident $clinicIncident): Response
    {
        $clinicIncident->load([
            'clinic:id,name',
            'space:id,code,name',
            'equipment:id,code,name,status',
            'clinicalSession:id,date,start_time,end_time,topic',
            'reporter:id,name',
            'resolver:id,name',
            'attachments',
        ]);

        return Inertia::render('admin/clinic-incidents/Show', [
            'incident' => $clinicIncident,
            ...$this->enums(),
        ]);
    }

    public function edit(ClinicIncident $clinicIncident): Response
    {
        return Inertia::render('admin/clinic-incidents/Edit', [
            'incident' => $clinicIncident,
            ...$this->formData($clinicIncident),
        ]);
    }

    public function update(ClinicIncidentRequest $request, ClinicIncident $clinicIncident): RedirectResponse
    {
        $clinicIncident->fill($request->validated());
        $this->syncResolution($clinicIncident, $request);
        $clinicIncident->save();

        return redirect()
            ->route('admin.clinic-incidents.show', $clinicIncident)
            ->with('success', 'Incidencia actualizada correctamente.');
    }

    public function destroy(ClinicIncident $clinicIncident): RedirectResponse
    {
        $clinicIncident->delete();

        return redirect()
            ->route('admin.clinic-incidents.index')
            ->with('success', 'Incidencia eliminada correctamente.');
    }

    /**
     * Registra quién y cuándo cerró la incidencia; si se reabre, limpia esos datos.
     */
    private function syncResolution(ClinicIncident $incident, Request $request): void
    {
        if ($incident->isClosed()) {
            if (! $incident->resolved_at) {
                $incident->resolved_at = now();
                $incident->resolved_by = $request->user()->id;
            }

            return;
        }

        $incident->resolved_at = null;
        $incident->resolved_by = null;
    }

    /**
     * ORDER BY según el orden de una lista fija de valores (portable, sin FIELD() de MySQL).
     * Los valores vienen de constantes del modelo, no del usuario.
     */
    private function orderByList(string $column, array $values): string
    {
        $cases = collect($values)
            ->map(fn ($value, $index) => "WHEN '{$value}' THEN {$index}")
            ->implode(' ');

        return "CASE {$column} {$cases} ELSE ".count($values).' END';
    }

    private function enums(): array
    {
        return [
            'types' => ClinicIncident::TYPES,
            'severities' => ClinicIncident::SEVERITIES,
            'statuses' => ClinicIncident::STATUSES,
        ];
    }

    private function formData(?ClinicIncident $incident = null): array
    {
        return [
            ...$this->enums(),
            'clinics' => Clinic::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'spaces' => Space::whereNotNull('clinic_id')->orderBy('name')->get(['id', 'clinic_id', 'code', 'name']),
            'equipment' => ClinicEquipment::where('status', '!=', 'baja')
                ->orderBy('name')
                ->get(['id', 'clinic_id', 'code', 'name']),
            // Sesiones recientes para relacionar la incidencia.
            'sessions' => ClinicalSession::whereDate('date', '>=', now()->subDays(60))
                ->when($incident?->clinical_session_id, fn ($q, $id) => $q->orWhere('id', $id))
                ->orderByDesc('date')
                ->get(['id', 'clinic_id', 'date', 'start_time', 'topic']),
        ];
    }
}
