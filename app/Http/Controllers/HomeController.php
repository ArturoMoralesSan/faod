<?php

namespace App\Http\Controllers;

use App\Models\AcademicActivity;
use App\Models\AcademicProgram;
use App\Models\Clinic;
use App\Models\Procedure;
use App\Models\Specialty;
use App\Models\StudyPlan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Sitio público de la facultad: oferta académica, cursos y actividades, y atención clínica.
 *
 * Solo expone información pública (nunca pacientes, alumnos ni datos de contacto personales):
 * cada método arma arreglos con los campos que se muestran.
 */
class HomeController extends Controller
{
    /** Orden en que se presentan los niveles de la oferta académica. */
    private const LEVELS = ['licenciatura', 'especialidad', 'maestria', 'doctorado', 'diplomado'];

    public function index(): Response
    {
        $programs = $this->programQuery()->get();

        return Inertia::render('public/Home', [
            'stats' => [
                'programs' => $programs->count(),
                'specialties' => Specialty::where('is_active', true)->count(),
                'clinics' => Clinic::where('is_active', true)->count(),
                'activities' => AcademicActivity::whereYear('starts_at', today()->year)->where('status', '!=', 'cancelada')->count(),
            ],
            'levels' => $this->levels($programs),
            'upcoming' => $this->publicActivities()->whereDate('starts_at', '>=', today())->orderBy('starts_at')->limit(3)->get()->map(fn ($a) => $this->activityCard($a)),
            'recent' => $this->publicActivities()->whereDate('starts_at', '<', today())->latest('starts_at')->limit(3)->get()->map(fn ($a) => $this->activityCard($a)),
            'specialties' => $this->specialties(),
            'clinics' => $this->clinics()->limit(3)->get()->map(fn ($c) => $this->clinicCard($c)),
            'servicesCount' => Procedure::where('is_active', true)->count(),
        ]);
    }

    /* ------------------------------ Oferta académica ------------------------------ */

    public function programs(Request $request): Response
    {
        $level = in_array($request->query('nivel'), self::LEVELS, true) ? $request->query('nivel') : null;

        return Inertia::render('public/Programs', [
            'levels' => $this->levels($this->programQuery()->get()),
            'level' => $level,
        ]);
    }

    public function program(string $code): Response
    {
        $program = $this->programQuery()
            ->where('code', $code)
            ->with(['objectives' => fn ($q) => $q->orderBy('sort')])
            ->firstOrFail();

        $plan = StudyPlan::query()
            ->where('academic_program_id', $program->id)
            ->where('is_active', true)
            ->with(['modules', 'subjects' => fn ($q) => $q->where('subjects.is_active', true)])
            ->latest('valid_from')
            ->first();

        return Inertia::render('public/Program', [
            'program' => [
                ...$this->programCard($program),
                'objectives' => $program->objectives->groupBy('type')->map(fn ($items) => $items->pluck('description')->values()),
            ],
            'plan' => $plan ? $this->planDetail($plan) : null,
            'related' => $this->programQuery()
                ->where('id', '!=', $program->id)
                ->where(fn ($q) => $q->where('type', $program->type)->orWhere('specialty_id', $program->specialty_id))
                ->limit(3)
                ->get()
                ->map(fn ($p) => $this->programCard($p)),
            'activities' => $this->publicActivities()
                ->whereDate('starts_at', '>=', today())
                ->orderBy('starts_at')
                ->limit(3)
                ->get()
                ->map(fn ($a) => $this->activityCard($a)),
        ]);
    }

    /* ------------------------------ Cursos y actividades ------------------------------ */

    public function activities(Request $request): Response
    {
        $type = in_array($request->query('tipo'), AcademicActivity::TYPES, true) ? $request->query('tipo') : null;
        $filtered = fn () => $this->publicActivities()->when($type, fn ($q) => $q->where('type', $type));

        return Inertia::render('public/Activities', [
            'upcoming' => $filtered()->whereDate('starts_at', '>=', today())->orderBy('starts_at')->get()->map(fn ($a) => $this->activityCard($a)),
            'past' => $filtered()->whereDate('starts_at', '<', today())->latest('starts_at')->paginate(9)->withQueryString()->through(fn ($a) => $this->activityCard($a)),
            'types' => AcademicActivity::TYPES,
            'type' => $type,
        ]);
    }

    public function activity(AcademicActivity $activity): Response
    {
        abort_if($activity->status === 'cancelada', 404);

        $activity->load([
            'space:id,name,code',
            'coordinator.people:id,first_name,middle_name,paternal_surname,maternal_surname',
            'instructors.teacher.people:id,first_name,middle_name,paternal_surname,maternal_surname',
        ])->loadCount(['participants as enrolled_count' => fn ($q) => $q->where('status', '!=', 'baja')]);

        return Inertia::render('public/Activity', [
            'activity' => [
                ...$this->activityCard($activity),
                'description' => $activity->description,
                'objectives' => $activity->objectives,
                'schedule' => $activity->schedule,
                'location' => $activity->space?->name ?? $activity->location,
                'coordinator' => $activity->coordinator?->people?->full_name,
                'instructors' => $activity->instructors->map(fn ($i) => [
                    'name' => $i->teacher?->people?->full_name ?? $i->name,
                    'role' => $i->role,
                    'internal' => (bool) $i->teacher,
                ])->values(),
                'results' => $activity->status === 'concluida' ? $activity->results : null,
            ],
            'more' => $this->publicActivities()
                ->where('id', '!=', $activity->id)
                ->whereDate('starts_at', '>=', today())
                ->orderBy('starts_at')
                ->limit(3)
                ->get()
                ->map(fn ($a) => $this->activityCard($a)),
        ]);
    }

    /* ------------------------------ Atención clínica ------------------------------ */

    public function clinical(): Response
    {
        $services = Procedure::query()
            ->where('is_active', true)
            ->with('specialty:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'category', 'specialty_id'])
            ->groupBy(fn ($p) => $p->specialty?->name ?? $p->category ?? 'Odontología general')
            ->map(fn ($items, $group) => ['group' => $group, 'items' => $items->pluck('name')->values()])
            ->sortBy('group')
            ->values();

        return Inertia::render('public/Clinical', [
            'clinics' => $this->clinics()->get()->map(fn ($c) => $this->clinicCard($c)),
            'services' => $services,
            'specialties' => $this->specialties(),
        ]);
    }

    /* ------------------------------ Consultas y formatos ------------------------------ */

    private function programQuery(): Builder
    {
        return AcademicProgram::query()
            ->where('is_active', true)
            ->with('specialty:id,name')
            ->orderByRaw("case type when 'licenciatura' then 1 when 'especialidad' then 2 when 'maestria' then 3 when 'doctorado' then 4 else 5 end")
            ->orderBy('name');
    }

    /** Programas agrupados por nivel, en el orden de LEVELS (solo los niveles con programas). */
    private function levels($programs): array
    {
        return collect(self::LEVELS)
            ->map(fn ($level) => [
                'level' => $level,
                'programs' => $programs->where('type', $level)->values()->map(fn ($p) => $this->programCard($p)),
            ])
            ->filter(fn ($row) => $row['programs']->isNotEmpty())
            ->values()
            ->all();
    }

    private function programCard(AcademicProgram $program): array
    {
        return [
            'code' => $program->code,
            'name' => $program->name,
            'type' => $program->type,
            'description' => $program->description,
            'duration_value' => $program->duration_value,
            'duration_unit' => $program->duration_unit,
            'total_periods' => $program->total_periods,
            'specialty' => $program->specialty?->name,
        ];
    }

    private function planDetail(StudyPlan $plan): array
    {
        $modules = $plan->modules->keyBy('id');

        return [
            'name' => $plan->name,
            'version' => $plan->version,
            'description' => $plan->description,
            'credits' => (float) $plan->subjects->sum('credits'),
            'subjects_count' => $plan->subjects->count(),
            'periods' => $plan->subjects
                ->groupBy(fn ($s) => (int) $s->pivot->period_number)
                ->sortKeys()
                ->map(fn ($subjects, $period) => [
                    'period' => $period,
                    'subjects' => $subjects->map(fn ($s) => [
                        'code' => $s->code,
                        'name' => $s->name,
                        'credits' => $s->credits,
                        'theory_hours' => $s->theory_hours,
                        'practice_hours' => $s->practice_hours,
                        'required' => (bool) $s->pivot->is_required,
                        'module' => $modules[$s->pivot->academic_module_id]?->name ?? null,
                    ])->values(),
                ])
                ->values(),
        ];
    }

    /** Actividades visibles al público: todas menos las canceladas. */
    private function publicActivities(): Builder
    {
        return AcademicActivity::query()
            ->where('status', '!=', 'cancelada')
            ->with('space:id,name')
            ->withCount(['participants as enrolled_count' => fn ($q) => $q->where('status', '!=', 'baja')]);
    }

    private function activityCard(AcademicActivity $activity): array
    {
        $available = $activity->capacity ? max(0, $activity->capacity - (int) $activity->enrolled_count) : null;

        return [
            'id' => $activity->id,
            'title' => $activity->title,
            'type' => $activity->type,
            'modality' => $activity->modality,
            'status' => $activity->status,
            'starts_at' => $activity->starts_at?->toDateString(),
            'ends_at' => $activity->ends_at?->toDateString(),
            'hours' => $activity->hours,
            'cost' => $activity->cost,
            'capacity' => $activity->capacity,
            'available' => $available,
            'issues_certificate' => (bool) $activity->issues_certificate,
            'place' => $activity->space?->name ?? $activity->location,
            'summary' => $activity->description ? mb_strimwidth(strip_tags($activity->description), 0, 180, '…') : null,
        ];
    }

    private function specialties(): array
    {
        return Specialty::query()
            ->where('is_active', true)
            ->withCount([
                'academicPrograms as programs_count' => fn ($q) => $q->where('is_active', true),
            ])
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'description'])
            ->map(fn ($s) => [
                'code' => $s->code,
                'name' => $s->name,
                'description' => $s->description,
                'programs' => $s->programs_count,
                'program_code' => AcademicProgram::where('specialty_id', $s->id)->where('is_active', true)->value('code'),
            ])
            ->all();
    }

    private function clinics(): Builder
    {
        return Clinic::query()
            ->where('is_active', true)
            ->with(['areas' => fn ($q) => $q->where('is_active', true)->with('specialty:id,name')])
            ->withCount(['spaces as offices_count' => fn ($q) => $q->where('type', 'consultorio')->where('is_active', true)])
            ->orderBy('name');
    }

    private function clinicCard(Clinic $clinic): array
    {
        return [
            'code' => $clinic->code,
            'name' => $clinic->name,
            'description' => $clinic->description,
            'location' => $clinic->location,
            'offices' => $clinic->offices_count,
            'areas' => $clinic->areas->map(fn ($a) => ['name' => $a->name, 'specialty' => $a->specialty?->name])->values(),
        ];
    }
}
