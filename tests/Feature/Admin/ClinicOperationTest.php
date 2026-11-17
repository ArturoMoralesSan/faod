<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicGroup;
use App\Models\AcademicPeriod;
use App\Models\Clinic;
use App\Models\ClinicalSession;
use App\Models\ClinicArea;
use App\Models\ClinicEquipment;
use App\Models\ClinicIncident;
use App\Models\People;
use App\Models\Space;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Módulo de clínicas: áreas, supervisores, equipos, disponibilidad,
 * sesiones clínicas e incidencias.
 */
class ClinicOperationTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinic;

    private Space $space;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->create()->assignRole('SuperAdmin'));

        $this->clinic = Clinic::create(['code' => 'CL-1', 'name' => 'Clínica de Licenciatura']);
        $this->space = Space::create([
            'clinic_id' => $this->clinic->id,
            'type' => 'consultorio',
            'code' => 'C-01',
            'name' => 'Consultorio 1',
        ]);
    }

    private function teacher(string $name = 'Ana'): TeacherProfile
    {
        $people = People::create(['first_name' => $name, 'paternal_surname' => 'Docente']);

        return TeacherProfile::create(['people_id' => $people->id, 'status' => 'active']);
    }

    private function student(string $registration): StudentProfile
    {
        $people = People::create(['first_name' => 'Alumno', 'paternal_surname' => $registration]);

        return StudentProfile::create(['people_id' => $people->id, 'registration_number' => $registration]);
    }

    /** Próximo lunes (día 1) para que las reglas por día de la semana sean predecibles. */
    private function nextMonday(): string
    {
        return now()->next('Monday')->toDateString();
    }

    private function sessionData(array $overrides = []): array
    {
        return [
            'clinic_id' => $this->clinic->id,
            'space_id' => $this->space->id,
            'date' => $this->nextMonday(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'programada',
            ...$overrides,
        ];
    }

    public function test_pages_render(): void
    {
        $session = ClinicalSession::create([...$this->sessionData(), 'start_time' => '09:00:00', 'end_time' => '11:00:00']);
        $incident = ClinicIncident::create([
            'clinic_id' => $this->clinic->id,
            'type' => 'equipo',
            'title' => 'Falla',
            'description' => 'No enciende',
            'occurred_at' => now(),
        ]);

        $pages = [
            [route('admin.clinics.show', $this->clinic), 'admin/clinics/Show'],
            [route('admin.spaces.show', $this->space), 'admin/spaces/Show'],
            [route('admin.spaces.create', ['clinic_id' => $this->clinic->id]), 'admin/spaces/Create'],
            [route('admin.clinical-sessions.index'), 'admin/clinical-sessions/Index'],
            [route('admin.clinical-sessions.create'), 'admin/clinical-sessions/Create'],
            [route('admin.clinical-sessions.show', $session), 'admin/clinical-sessions/Show'],
            [route('admin.clinical-sessions.edit', $session), 'admin/clinical-sessions/Edit'],
            [route('admin.clinic-incidents.index'), 'admin/clinic-incidents/Index'],
            [route('admin.clinic-incidents.create'), 'admin/clinic-incidents/Create'],
            [route('admin.clinic-incidents.show', $incident), 'admin/clinic-incidents/Show'],
            [route('admin.clinic-incidents.edit', $incident), 'admin/clinic-incidents/Edit'],
        ];

        foreach ($pages as [$url, $component]) {
            $this->get($url)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component($component));
        }
    }

    public function test_clinic_records_are_managed_from_the_clinic(): void
    {
        $teacher = $this->teacher();

        $this->post(route('admin.clinics.records.store', [$this->clinic, 'areas']), ['name' => 'Endodoncia', 'is_active' => true])
            ->assertSessionHasNoErrors();
        $area = ClinicArea::firstWhere('name', 'Endodoncia');

        $this->post(route('admin.clinics.records.store', [$this->clinic, 'supervisors']), [
            'teacher_profile_id' => $teacher->id,
            'clinic_area_id' => $area->id,
            'role' => 'responsable',
        ])->assertSessionHasNoErrors();

        $this->post(route('admin.clinics.records.store', [$this->clinic, 'equipment']), [
            'code' => 'INV-001',
            'name' => 'Unidad dental',
            'space_id' => $this->space->id,
            'status' => 'operativo',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clinic_supervisors', ['clinic_id' => $this->clinic->id, 'teacher_profile_id' => $teacher->id]);
        $this->assertDatabaseHas('clinic_equipment', ['clinic_id' => $this->clinic->id, 'code' => 'INV-001']);

        // Un área de otra clínica no se puede usar.
        $other = Clinic::create(['code' => 'CL-2', 'name' => 'Otra']);
        $otherArea = ClinicArea::create(['clinic_id' => $other->id, 'name' => 'Rayos X']);

        $this->post(route('admin.clinics.records.store', [$this->clinic, 'supervisors']), [
            'teacher_profile_id' => $teacher->id,
            'clinic_area_id' => $otherArea->id,
            'role' => 'supervisor',
        ])->assertSessionHasErrors('clinic_area_id');

        // Un registro de otra clínica no se puede editar desde esta.
        $this->put(route('admin.clinics.records.update', [$this->clinic, 'areas', $otherArea->id]), ['name' => 'X'])
            ->assertNotFound();
    }

    public function test_session_with_group_attaches_students_and_saves_attendance(): void
    {
        $period = AcademicPeriod::create([
            'code' => '2026-2',
            'name' => '2026-2',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonths(3),
            'is_current' => true,
        ]);

        $cohortId = DB::table('cohorts')->insertGetId([
            'academic_program_id' => DB::table('academic_programs')->insertGetId([
                'type' => 'licenciatura', 'code' => 'LCD', 'name' => 'Cirujano Dentista', 'created_at' => now(), 'updated_at' => now(),
            ]),
            'name' => 'Gen 2026', 'starts_at' => now()->subYear(), 'status' => 'en_curso', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $group = AcademicGroup::create([
            'cohort_id' => $cohortId,
            'academic_period_id' => $period->id,
            'period_number' => 1,
            'code' => '1A',
        ]);
        $students = [$this->student('A001'), $this->student('A002')];
        $group->students()->sync(collect($students)->pluck('id'));

        $this->post(route('admin.clinical-sessions.store'), $this->sessionData(['academic_group_id' => $group->id]))
            ->assertSessionHasNoErrors();

        $session = ClinicalSession::latest('id')->first();

        // El periodo se toma de la fecha y los alumnos del grupo se agregan.
        $this->assertSame($period->id, $session->academic_period_id);
        $this->assertCount(2, $session->students);

        $this->put(route('admin.clinical-sessions.attendance', $session), [
            'students' => [
                ['student_profile_id' => $students[0]->id, 'attendance' => 'asistio'],
                ['student_profile_id' => $students[1]->id, 'attendance' => 'falta', 'notes' => 'Sin aviso'],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clinical_session_student', [
            'clinical_session_id' => $session->id,
            'student_profile_id' => $students[1]->id,
            'attendance' => 'falta',
        ]);

        // Una lista vacía quita a todos.
        $this->put(route('admin.clinical-sessions.attendance', $session), ['students' => []])
            ->assertSessionHasNoErrors();
        $this->assertCount(0, $session->fresh()->students);
    }

    public function test_session_conflicts_are_rejected(): void
    {
        $teacher = $this->teacher();

        $this->post(route('admin.clinical-sessions.store'), $this->sessionData(['supervisor_id' => $teacher->id]))
            ->assertSessionHasNoErrors();

        // Mismo consultorio, horario encimado.
        $this->post(route('admin.clinical-sessions.store'), $this->sessionData(['start_time' => '10:00', 'end_time' => '12:00']))
            ->assertSessionHasErrors('space_id');

        // Mismo supervisor en otro consultorio.
        $otherSpace = Space::create(['clinic_id' => $this->clinic->id, 'type' => 'consultorio', 'code' => 'C-02', 'name' => 'Consultorio 2']);
        $this->post(route('admin.clinical-sessions.store'), $this->sessionData(['space_id' => $otherSpace->id, 'supervisor_id' => $teacher->id]))
            ->assertSessionHasErrors('supervisor_id');

        // Sesiones contiguas sí se permiten.
        $this->post(route('admin.clinical-sessions.store'), $this->sessionData(['start_time' => '11:00', 'end_time' => '13:00']))
            ->assertSessionHasNoErrors();

        // Una sesión cancelada no bloquea el horario.
        ClinicalSession::query()->update(['status' => 'cancelada']);
        $this->post(route('admin.clinical-sessions.store'), $this->sessionData())
            ->assertSessionHasNoErrors();

        // Consultorio de otra clínica.
        $other = Clinic::create(['code' => 'CL-2', 'name' => 'Otra']);
        $this->post(route('admin.clinical-sessions.store'), $this->sessionData(['clinic_id' => $other->id]))
            ->assertSessionHasErrors('space_id');
    }

    public function test_session_must_fit_space_availability(): void
    {
        // Lunes de 08:00 a 12:00.
        $this->post(route('admin.spaces.availabilities.store', $this->space), [
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '12:00',
        ])->assertSessionHasNoErrors();

        // Bloque encimado con el anterior.
        $this->post(route('admin.spaces.availabilities.store', $this->space), [
            'day_of_week' => 1,
            'start_time' => '11:00',
            'end_time' => '14:00',
        ])->assertSessionHasErrors('start_time');

        $this->post(route('admin.clinical-sessions.store'), $this->sessionData(['start_time' => '11:00', 'end_time' => '13:00']))
            ->assertSessionHasErrors('start_time');

        $this->post(route('admin.clinical-sessions.store'), $this->sessionData(['date' => now()->next('Tuesday')->toDateString()]))
            ->assertSessionHasErrors('start_time');

        $this->post(route('admin.clinical-sessions.store'), $this->sessionData(['start_time' => '08:00', 'end_time' => '12:00']))
            ->assertSessionHasNoErrors();
    }

    public function test_incident_resolution_is_tracked(): void
    {
        $equipment = ClinicEquipment::create(['clinic_id' => $this->clinic->id, 'code' => 'INV-9', 'name' => 'Autoclave']);

        $data = [
            'clinic_id' => $this->clinic->id,
            'clinic_equipment_id' => $equipment->id,
            'type' => 'equipo',
            'severity' => 'alta',
            'status' => 'abierta',
            'title' => 'Autoclave no llega a temperatura',
            'description' => 'Se detuvo a mitad del ciclo.',
            'occurred_at' => now()->subHour()->format('Y-m-d\TH:i'),
        ];

        $this->post(route('admin.clinic-incidents.store'), $data)->assertSessionHasNoErrors();
        $incident = ClinicIncident::latest('id')->first();
        $this->assertNotNull($incident->reported_by);
        $this->assertNull($incident->resolved_at);

        // Cerrarla exige resolución.
        $this->put(route('admin.clinic-incidents.update', $incident), [...$data, 'status' => 'resuelta'])
            ->assertSessionHasErrors('resolution');

        $this->put(route('admin.clinic-incidents.update', $incident), [...$data, 'status' => 'resuelta', 'resolution' => 'Se cambió la resistencia.'])
            ->assertSessionHasNoErrors();
        $this->assertNotNull($incident->fresh()->resolved_at);
        $this->assertNotNull($incident->fresh()->resolved_by);

        // Reabrirla limpia la fecha de cierre.
        $this->put(route('admin.clinic-incidents.update', $incident), [...$data, 'status' => 'en_proceso'])
            ->assertSessionHasNoErrors();
        $this->assertNull($incident->fresh()->resolved_at);

        // Equipo de otra clínica.
        $other = Clinic::create(['code' => 'CL-2', 'name' => 'Otra']);
        $this->post(route('admin.clinic-incidents.store'), [...$data, 'clinic_id' => $other->id])
            ->assertSessionHasErrors('clinic_equipment_id');
    }
}
