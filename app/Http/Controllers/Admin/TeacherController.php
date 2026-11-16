<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\ClassSchedule;
use App\Models\KnowledgeArea;
use App\Models\Specialty;
use App\Models\TeacherDegree;
use App\Models\TeacherProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestión de docentes y especialistas.
 * Los datos personales y el perfil base se editan en PeopleController.
 */
class TeacherController extends Controller
{
    public function index(Request $request): Response
    {
        $teachers = TeacherProfile::query()
            ->with([
                'people:id,first_name,paternal_surname,maternal_surname,phone,personal_email',
                'specialties:id,name',
                'academicPrograms:id,name,code',
            ])
            ->when($request->search, fn ($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('employee_number', 'like', "%{$search}%")
                    ->orWhere('professional_license', 'like', "%{$search}%")
                    ->orWhereHas('people', fn ($q) => $q
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('paternal_surname', 'like', "%{$search}%")
                        ->orWhere('maternal_surname', 'like', "%{$search}%"));
            }))
            ->when($request->specialty_id, fn ($q, $id) => $q->whereHas('specialties', fn ($q) => $q->whereKey($id)))
            ->when($request->academic_program_id, fn ($q, $id) => $q->whereHas('academicPrograms', fn ($q) => $q->whereKey($id)))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/teachers/Index', [
            'teachers' => $teachers,
            'filters' => $request->only('search', 'specialty_id', 'academic_program_id', 'status'),
            'specialties' => Specialty::orderBy('name')->get(['id', 'name']),
            'programs' => AcademicProgram::orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * Expediente del docente: formación, experiencia, disponibilidad
     * e historial de participación académica.
     */
    public function show(TeacherProfile $teacher): Response
    {
        $teacher->load([
            'people',
            'specialties:id,name',
            'knowledgeAreas:id,name',
            'degrees',
            'certifications',
            'experiences',
            'availabilities.academicPeriod:id,name',
            'academicPrograms:id,name,code',
            'classSections.subject:id,code,name',
            'classSections.group:id,code,academic_period_id',
            'classSections.group.academicPeriod:id,name',
            'classSections.schedules.space:id,code,name',
            'attachments',
        ]);

        return Inertia::render('admin/teachers/Show', [
            'teacher' => $teacher,
            'days' => ClassSchedule::DAYS,
            'degreeLevels' => TeacherDegree::LEVELS,
            'specialties' => Specialty::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'knowledgeAreas' => KnowledgeArea::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Sincroniza especialidades y áreas de conocimiento.
     */
    public function syncAreas(Request $request, TeacherProfile $teacher): RedirectResponse
    {
        $data = $request->validate([
            'specialty_ids' => ['present', 'array'],
            'specialty_ids.*' => ['integer', 'exists:specialties,id'],
            'knowledge_area_ids' => ['present', 'array'],
            'knowledge_area_ids.*' => ['integer', 'exists:knowledge_areas,id'],
        ]);

        DB::transaction(function () use ($teacher, $data) {
            $teacher->specialties()->sync($data['specialty_ids']);
            $teacher->knowledgeAreas()->sync($data['knowledge_area_ids']);
        });

        return back()->with('success', 'Especialidades y áreas actualizadas.');
    }
}
