<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicGroup;
use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\Cohort;
use App\Models\People;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Alumno y docente solo consultan, y solo lo suyo.
 */
class RoleScopeTest extends TestCase
{
    use RefreshDatabase;

    private AcademicGroup $mine;

    private AcademicGroup $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $period = AcademicPeriod::create(['code' => '2026-B', 'name' => 'Ago - Dic 2026', 'starts_at' => '2026-08-01', 'ends_at' => '2026-12-15', 'is_current' => true]);
        $program = AcademicProgram::create(['type' => 'licenciatura', 'code' => 'LCD', 'name' => 'Cirujano Dentista']);
        $cohort = Cohort::create(['academic_program_id' => $program->id, 'name' => '2026-2031']);

        $this->mine = AcademicGroup::create(['cohort_id' => $cohort->id, 'academic_period_id' => $period->id, 'period_number' => 1, 'code' => '1A']);
        $this->other = AcademicGroup::create(['cohort_id' => $cohort->id, 'academic_period_id' => $period->id, 'period_number' => 1, 'code' => '1B']);
    }

    private function teacher(): array
    {
        $user = User::factory()->create()->assignRole('docente');
        $people = People::create(['user_id' => $user->id, 'first_name' => 'Ana', 'paternal_surname' => 'Soto']);
        $teacher = TeacherProfile::create(['people_id' => $people->id]);

        $subject = Subject::create(['code' => 'LCD-0101', 'name' => 'Anatomía']);
        $this->mine->classSections()->create(['subject_id' => $subject->id, 'teacher_profile_id' => $teacher->id]);

        return [$user, $teacher];
    }

    private function student(): User
    {
        $user = User::factory()->create()->assignRole('alumno');
        $people = People::create(['user_id' => $user->id, 'first_name' => 'Luis', 'paternal_surname' => 'Ríos']);
        $student = StudentProfile::create(['people_id' => $people->id, 'registration_number' => '202600001']);
        $this->mine->students()->attach($student->id);

        return $user;
    }

    public function test_student_cannot_create_patients_appointments_or_manage_groups(): void
    {
        $this->actingAs($this->student());

        $this->get(route('admin.patients.create'))->assertForbidden();
        $this->get(route('admin.appointments.create'))->assertForbidden();
        $this->get(route('admin.groups.index'))->assertForbidden();
        $this->get(route('admin.patients.index'))->assertOk();
    }

    public function test_student_sees_only_his_schedule(): void
    {
        $this->actingAs($this->student());

        $this->get(route('admin.my-schedule.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/my-schedule/Index')
                ->has('groups', 1)
                ->where('groups.0.code', '1A'));

        $this->get(route('admin.my-schedule.pdf', $this->other))->assertForbidden();
    }

    public function test_teacher_only_reads_and_only_his_own(): void
    {
        [$user] = $this->teacher();
        $this->actingAs($user);

        $this->get(route('admin.groups.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('groups.data', 1)->where('groups.data.0.code', '1A'));
        $this->get(route('admin.groups.show', $this->mine))->assertOk();
        $this->get(route('admin.groups.show', $this->other))->assertNotFound();

        $this->get(route('admin.groups.create'))->assertForbidden();
        $this->get(route('admin.subjects.index'))->assertForbidden();
        $this->get(route('admin.academic-programs.create'))->assertForbidden();
        $this->get(route('admin.patients.create'))->assertForbidden();
        $this->get(route('admin.appointments.index'))->assertForbidden();
        $this->get(route('admin.clinical-sessions.index'))->assertForbidden();
        $this->get(route('admin.inventory.index'))->assertForbidden();
        $this->get(route('admin.teachers.index'))->assertForbidden();
    }

    public function test_teacher_sees_his_own_record_read_only(): void
    {
        [$user, $teacher] = $this->teacher();

        $this->actingAs($user)
            ->get(route('admin.my-record'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/teachers/Show')
                ->where('teacher.id', $teacher->id)
                ->where('canEdit', false)
                ->where('ownRecord', true));
    }
}
