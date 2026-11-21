<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicGroup;
use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\ClassSchedule;
use App\Models\ClassSection;
use App\Models\Cohort;
use App\Models\People;
use App\Models\Space;
use App\Models\Subject;
use App\Models\TeacherAvailability;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Support\WeeklyScheduleGrid;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Horarios de grupo: choques, disponibilidad del docente y horario imprimible.
 */
class ClassScheduleTest extends TestCase
{
    use RefreshDatabase;

    private AcademicPeriod $period;

    private AcademicGroup $group;

    private Space $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->create()->assignRole('SuperAdmin'));

        $this->period = AcademicPeriod::create([
            'code' => '2026-2', 'name' => '2026-2',
            'starts_at' => now()->subMonth(), 'ends_at' => now()->addMonths(3), 'is_current' => true,
        ]);
        $program = AcademicProgram::create(['type' => 'licenciatura', 'code' => 'LCD', 'name' => 'Cirujano Dentista']);
        $cohort = Cohort::create(['academic_program_id' => $program->id, 'name' => 'Gen 2026', 'starts_at' => now()->subYear(), 'status' => 'en_curso']);

        $this->group = $this->group('1A', $cohort->id);
        $this->room = Space::create(['type' => 'aula', 'code' => 'A-101', 'name' => 'Aula 101']);
    }

    private function group(string $code, ?int $cohortId = null): AcademicGroup
    {
        return AcademicGroup::create([
            'cohort_id' => $cohortId ?? $this->group->cohort_id,
            'academic_period_id' => $this->period->id,
            'period_number' => 1,
            'code' => $code,
        ]);
    }

    private function teacher(string $name = 'Laura'): TeacherProfile
    {
        $people = People::create(['first_name' => $name, 'paternal_surname' => 'Docente']);

        return TeacherProfile::create(['people_id' => $people->id, 'status' => 'active']);
    }

    private function section(?TeacherProfile $teacher = null, ?AcademicGroup $group = null, string $subject = 'Anatomía'): ClassSection
    {
        $subject = Subject::create(['code' => mb_strtoupper(mb_substr($subject, 0, 3)).'-'.Subject::count(), 'name' => $subject]);

        return ($group ?? $this->group)->classSections()->create([
            'subject_id' => $subject->id,
            'teacher_profile_id' => $teacher?->id,
        ]);
    }

    private function schedule(ClassSection $section, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('admin.class-schedules.store'), [
            'class_section_id' => $section->id,
            'space_id' => $this->room->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '10:00',
            ...$overrides,
        ]);
    }

    public function test_schedule_block_is_saved(): void
    {
        $section = $this->section($this->teacher());

        $this->schedule($section)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('class_schedules', [
            'class_section_id' => $section->id,
            'space_id' => $this->room->id,
            'day_of_week' => 1,
        ]);
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $this->schedule($this->section(), ['start_time' => '10:00', 'end_time' => '09:00'])
            ->assertSessionHasErrors('end_time');
    }

    public function test_room_teacher_and_group_conflicts_are_rejected(): void
    {
        $teacher = $this->teacher();
        $this->schedule($this->section($teacher))->assertSessionHasNoErrors();

        // Mismo grupo, otra materia, otra aula: choca el grupo.
        $otherRoom = Space::create(['type' => 'aula', 'code' => 'A-102', 'name' => 'Aula 102']);
        $this->schedule($this->section(subject: 'Histología'), ['space_id' => $otherRoom->id, 'start_time' => '09:00', 'end_time' => '11:00'])
            ->assertSessionHasErrors(['start_time' => 'El grupo ya tiene otra materia en ese horario.']);

        // Otro grupo, mismo docente, otra aula: choca el docente.
        $this->schedule($this->section($teacher, $this->group('1B'), 'Bioquímica'), ['space_id' => $otherRoom->id])
            ->assertSessionHasErrors(['start_time' => 'El docente ya tiene clase en ese horario.']);

        // Otro grupo y docente, misma aula: choca el aula.
        $this->schedule($this->section($this->teacher('Pedro'), $this->group('1C'), 'Fisiología'))
            ->assertSessionHasErrors('space_id');

        // Justo al terminar la clase anterior no hay choque.
        $this->schedule($this->section($teacher, $this->group('1D'), 'Farmacología'), ['start_time' => '10:00', 'end_time' => '12:00'])
            ->assertSessionHasNoErrors();
    }

    public function test_other_periods_do_not_conflict(): void
    {
        $teacher = $this->teacher();
        $this->schedule($this->section($teacher))->assertSessionHasNoErrors();

        $next = AcademicPeriod::create(['code' => '2027-1', 'name' => '2027-1', 'starts_at' => now()->addMonths(4), 'ends_at' => now()->addMonths(8)]);
        $nextGroup = AcademicGroup::create(['cohort_id' => $this->group->cohort_id, 'academic_period_id' => $next->id, 'period_number' => 2, 'code' => '2A']);

        $this->schedule($this->section($teacher, $nextGroup, 'Prótesis'))->assertSessionHasNoErrors();
    }

    public function test_editing_a_block_does_not_conflict_with_itself(): void
    {
        $section = $this->section($this->teacher());
        $this->schedule($section);
        $block = ClassSchedule::first();

        $this->put(route('admin.class-schedules.update', $block), [
            'class_section_id' => $section->id,
            'space_id' => $this->room->id,
            'day_of_week' => 1,
            'start_time' => '08:30',
            'end_time' => '10:30',
        ])->assertSessionHasNoErrors();

        $this->assertSame('08:30', substr($block->fresh()->start_time, 0, 5));
    }

    public function test_class_must_fit_the_teacher_availability(): void
    {
        $teacher = $this->teacher();
        TeacherAvailability::create(['teacher_profile_id' => $teacher->id, 'day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '12:00']);
        TeacherAvailability::create(['teacher_profile_id' => $teacher->id, 'academic_period_id' => $this->period->id, 'day_of_week' => 3, 'start_time' => '16:00', 'end_time' => '19:00']);
        $section = $this->section($teacher);

        // Dentro de la disponibilidad general (sin periodo) y de la del periodo.
        $this->schedule($section, ['start_time' => '10:00', 'end_time' => '12:00'])->assertSessionHasNoErrors();
        $this->schedule($section, ['day_of_week' => 3, 'start_time' => '16:00', 'end_time' => '18:00', 'space_id' => null])->assertSessionHasNoErrors();

        // Se pasa del bloque disponible.
        $this->schedule($section, ['start_time' => '11:00', 'end_time' => '13:00', 'space_id' => null])
            ->assertSessionHasErrors(['start_time' => 'El horario está fuera de la disponibilidad del docente (Lunes: 08:00–12:00).']);

        // Día sin disponibilidad.
        $this->schedule($section, ['day_of_week' => 2, 'space_id' => null])
            ->assertSessionHasErrors(['start_time' => 'El horario está fuera de la disponibilidad del docente (Martes: sin disponibilidad).']);
    }

    public function test_availability_of_another_period_is_ignored(): void
    {
        $teacher = $this->teacher();
        $other = AcademicPeriod::create(['code' => '2027-1', 'name' => '2027-1', 'starts_at' => now()->addMonths(4), 'ends_at' => now()->addMonths(8)]);
        TeacherAvailability::create(['teacher_profile_id' => $teacher->id, 'academic_period_id' => $other->id, 'day_of_week' => 5, 'start_time' => '08:00', 'end_time' => '09:00']);

        // Para el periodo del grupo no registró disponibilidad: se le puede programar a cualquier hora.
        $this->schedule($this->section($teacher))->assertSessionHasNoErrors();
    }

    public function test_changing_the_teacher_checks_conflicts_and_availability(): void
    {
        $section = $this->section($this->teacher());
        $this->schedule($section)->assertSessionHasNoErrors();

        // Docente ocupado a esa hora en otro grupo.
        $busy = $this->teacher('Pedro');
        $other = $this->section($busy, $this->group('1B'), 'Fisiología');
        $this->schedule($other, ['space_id' => null])->assertSessionHasNoErrors();

        $this->put(route('admin.class-sections.update', $section), ['teacher_profile_id' => $busy->id])
            ->assertSessionHasErrors(['teacher_profile_id' => 'El docente ya tiene clase el Lunes 08:00–10:00.']);

        // Docente que solo está disponible por la tarde.
        $afternoon = $this->teacher('Rosa');
        TeacherAvailability::create(['teacher_profile_id' => $afternoon->id, 'day_of_week' => 1, 'start_time' => '14:00', 'end_time' => '20:00']);

        $this->put(route('admin.class-sections.update', $section), ['teacher_profile_id' => $afternoon->id])
            ->assertSessionHasErrors('teacher_profile_id');

        // Docente libre: se asigna.
        $free = $this->teacher('Ana');
        $this->put(route('admin.class-sections.update', $section), ['teacher_profile_id' => $free->id])
            ->assertSessionHasNoErrors();
        $this->assertSame($free->id, $section->fresh()->teacher_profile_id);

        // Quitar el docente siempre se puede.
        $this->put(route('admin.class-sections.update', $section), ['teacher_profile_id' => null])
            ->assertSessionHasNoErrors();
    }

    public function test_removing_a_subject_removes_its_schedule(): void
    {
        $section = $this->section();
        $this->schedule($section);

        $this->delete(route('admin.class-sections.destroy', $section))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('class_schedules', 0);
    }

    public function test_schedule_pdfs_for_group_teacher_and_space(): void
    {
        $teacher = $this->teacher();
        $this->schedule($this->section($teacher))->assertSessionHasNoErrors();

        foreach ([
            route('admin.groups.schedule-pdf', $this->group),
            route('admin.teachers.schedule-pdf', $teacher),
            route('admin.spaces.schedule-pdf', $this->room),
        ] as $url) {
            $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $response->getContent());
        }

        // Grupo sin clases: también genera el PDF (con el aviso de que está vacío).
        $this->get(route('admin.groups.schedule-pdf', $this->group('1Z')))->assertOk();
    }

    public function test_teacher_record_shows_only_the_selected_period(): void
    {
        $teacher = $this->teacher();
        $this->schedule($this->section($teacher))->assertSessionHasNoErrors();

        $next = AcademicPeriod::create(['code' => '2027-1', 'name' => '2027-1', 'starts_at' => now()->addMonths(4), 'ends_at' => now()->addMonths(8)]);
        $nextGroup = AcademicGroup::create(['cohort_id' => $this->group->cohort_id, 'academic_period_id' => $next->id, 'period_number' => 2, 'code' => '2A']);
        $this->schedule($this->section($teacher, $nextGroup, 'Prótesis'))->assertSessionHasNoErrors();

        // Por omisión, el periodo vigente.
        $this->get(route('admin.teachers.show', $teacher))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('academicPeriodId', $this->period->id)
                ->has('teacher.class_sections', 1)
                ->where('teacher.class_sections.0.group.code', '1A'));

        $this->get(route('admin.teachers.show', [$teacher, 'academic_period_id' => $next->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('academicPeriodId', $next->id)
                ->has('teacher.class_sections', 1)
                ->where('teacher.class_sections.0.group.code', '2A'));
    }

    public function test_schedule_pdf_requires_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.groups.schedule-pdf', $this->group))->assertForbidden();
        $this->get(route('admin.spaces.schedule-pdf', $this->room))->assertForbidden();
    }

    public function test_grid_covers_the_hours_and_days_with_classes(): void
    {
        $grid = new WeeklyScheduleGrid([
            ['day' => 1, 'start' => '08:30', 'end' => '10:00', 'title' => 'A', 'lines' => [], 'color' => 0],
            ['day' => 2, 'start' => '09:00', 'end' => '11:15', 'title' => 'B', 'lines' => [], 'color' => 1],
            ['day' => 6, 'start' => '08:00:00', 'end' => '09:00:00', 'title' => 'C', 'lines' => [], 'color' => 2],
        ]);

        // Lunes a viernes más el sábado, que tiene clase; el domingo no aparece.
        $this->assertSame([1, 2, 3, 4, 5, 6], array_keys($grid->days));

        // De las 08:00 a las 12:00 (se redondea a horas completas).
        $this->assertSame([8, 9, 10, 11], $grid->hours());
        $this->assertSame(240, $grid->totalMinutes());
        $this->assertSame(30, $grid->offset('08:30'));
        $this->assertSame('08:00', $grid->blocks[6][0]['start']);
        $this->assertSame(1, $grid->blocks[1][0]['lanes']);
    }

    public function test_grid_splits_overlapping_blocks_into_lanes(): void
    {
        $grid = new WeeklyScheduleGrid([
            ['day' => 1, 'start' => '08:00', 'end' => '10:00', 'title' => 'A', 'lines' => [], 'color' => 0],
            ['day' => 1, 'start' => '09:00', 'end' => '11:00', 'title' => 'B', 'lines' => [], 'color' => 1],
            ['day' => 1, 'start' => '11:00', 'end' => '12:00', 'title' => 'C', 'lines' => [], 'color' => 2],
        ]);

        [$a, $b, $c] = $grid->blocks[1];

        $this->assertSame([0, 2], [$a['lane'], $a['lanes']]);
        $this->assertSame([1, 2], [$b['lane'], $b['lanes']]);
        // El que empieza cuando terminan los otros ocupa todo el ancho.
        $this->assertSame([0, 1], [$c['lane'], $c['lanes']]);
        $this->assertTrue((new WeeklyScheduleGrid([]))->isEmpty());
    }
}
