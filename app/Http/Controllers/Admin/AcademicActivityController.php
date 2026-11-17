<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicActivity;
use App\Models\ActivityParticipant;
use App\Models\Space;
use App\Models\TeacherProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Cursos, seminarios, congresos y demás actividades académicas.
 */
class AcademicActivityController extends Controller
{
    public function index(Request $request): Response
    {
        $activities = AcademicActivity::query()
            ->with(['coordinator.people:id,first_name,paternal_surname', 'space:id,code,name'])
            ->withCount(['participants' => fn ($q) => $q->where('status', '!=', 'baja')])
            ->when($request->search, fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('starts_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/activities/Index', [
            'activities' => $activities,
            'filters' => $request->only('search', 'type', 'status'),
            'types' => AcademicActivity::TYPES,
            'statuses' => AcademicActivity::STATUSES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/activities/Create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $activity = AcademicActivity::create([...$this->validated($request), 'created_by' => $request->user()->id]);

        return redirect()->route('admin.activities.show', $activity)->with('success', 'Actividad registrada.');
    }

    public function show(AcademicActivity $activity): Response
    {
        $people = 'people:id,first_name,middle_name,paternal_surname,maternal_surname,personal_email,phone';

        $activity->load([
            'coordinator.people:id,first_name,paternal_surname,maternal_surname',
            'space:id,code,name',
            'instructors.teacher.people:id,first_name,paternal_surname,maternal_surname',
            'participants' => fn ($q) => $q->with([
                $people,
                'people.studentProfile:id,people_id,registration_number',
                'people.teacherProfile:id,people_id',
            ]),
            'attachments',
        ]);

        return Inertia::render('admin/activities/Show', [
            'activity' => $activity,
            'participantStatuses' => ActivityParticipant::STATUSES,
            'paymentStatuses' => ActivityParticipant::PAYMENT_STATUSES,
            'teachers' => $this->teachers(),
            'peopleSearchUrl' => route('admin.people.search'),
        ]);
    }

    public function edit(AcademicActivity $activity): Response
    {
        return Inertia::render('admin/activities/Edit', [
            'activity' => $activity,
            ...$this->formData(),
        ]);
    }

    public function update(Request $request, AcademicActivity $activity): RedirectResponse
    {
        $activity->update($this->validated($request));

        return redirect()->route('admin.activities.show', $activity)->with('success', 'Actividad actualizada.');
    }

    public function destroy(AcademicActivity $activity): RedirectResponse
    {
        $activity->delete();

        return redirect()->route('admin.activities.index')->with('success', 'Actividad eliminada.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(AcademicActivity::TYPES)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'modality' => ['required', Rule::in(AcademicActivity::MODALITIES)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'schedule' => ['nullable', 'string', 'max:255'],
            'space_id' => ['nullable', Rule::exists('spaces', 'id')->whereNull('deleted_at')],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'hours' => ['nullable', 'integer', 'min:1'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(AcademicActivity::STATUSES)],
            'coordinator_id' => ['nullable', Rule::exists('teacher_profiles', 'id')->whereNull('deleted_at')],
            'issues_certificate' => ['boolean'],
            'min_attendance' => ['required', 'integer', 'between:0,100'],
        ]);
    }

    private function teachers()
    {
        return TeacherProfile::where('status', 'active')
            ->with('people:id,first_name,paternal_surname,maternal_surname')
            ->get(['id', 'people_id']);
    }

    private function formData(): array
    {
        return [
            'types' => AcademicActivity::TYPES,
            'modalities' => AcademicActivity::MODALITIES,
            'statuses' => AcademicActivity::STATUSES,
            'spaces' => Space::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'teachers' => $this->teachers(),
        ];
    }
}
