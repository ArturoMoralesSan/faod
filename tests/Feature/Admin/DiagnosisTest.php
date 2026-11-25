<?php

namespace Tests\Feature\Admin;

use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\PatientDiagnosis;
use App\Models\People;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DiagnosisTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->create()->assignRole('SuperAdmin'));

        $people = People::create(['first_name' => 'Luis', 'paternal_surname' => 'Paciente']);
        $this->patient = Patient::create(['people_id' => $people->id, 'registered_at' => now()]);
    }

    private function diagnose(array $overrides = []): PatientDiagnosis
    {
        $this->post(route('admin.patients.diagnoses.store', $this->patient), [
            'diagnosis_id' => Diagnosis::firstWhere('code', 'K02.1')->id,
            'tooth' => '36',
            'surfaces' => 'DOM',
            'type' => 'presuntivo',
            'diagnosed_at' => now()->toDateString(),
            ...$overrides,
        ])->assertSessionHasNoErrors();

        return PatientDiagnosis::latest('id')->first();
    }

    public function test_diagnosis_is_created_pending_with_normalized_surfaces(): void
    {
        $diagnosis = $this->diagnose();

        $this->assertSame('pendiente', $diagnosis->review_status);
        $this->assertSame('MOD', $diagnosis->surfaces);

        // Sin catálogo ni descripción no se acepta.
        $this->post(route('admin.patients.diagnoses.store', $this->patient), [
            'type' => 'presuntivo',
            'diagnosed_at' => now()->toDateString(),
        ])->assertSessionHasErrors(['diagnosis_id', 'description']);

        // Diente inexistente en la nomenclatura FDI.
        $this->post(route('admin.patients.diagnoses.store', $this->patient), [
            'description' => 'X',
            'tooth' => '19',
            'type' => 'presuntivo',
            'diagnosed_at' => now()->toDateString(),
        ])->assertSessionHasErrors('tooth');
    }

    public function test_review_flow_and_edit_requires_new_review(): void
    {
        $diagnosis = $this->diagnose();

        // Pedir correcciones exige comentario.
        $this->post(route('admin.reviews.store'), [
            'reviewable_type' => 'patient_diagnosis',
            'reviewable_id' => $diagnosis->id,
            'decision' => 'correcciones',
        ])->assertSessionHasErrors('comments');

        $this->post(route('admin.reviews.store'), [
            'reviewable_type' => 'patient_diagnosis',
            'reviewable_id' => $diagnosis->id,
            'decision' => 'correcciones',
            'comments' => 'Revisar la cara distal.',
        ])->assertSessionHasNoErrors();
        $this->assertSame('correcciones', $diagnosis->fresh()->review_status);

        // El alumno corrige: vuelve a pendiente.
        $this->put(route('admin.patients.diagnoses.update', [$this->patient, $diagnosis]), [
            'diagnosis_id' => $diagnosis->diagnosis_id,
            'tooth' => '36',
            'surfaces' => 'MO',
            'type' => 'definitivo',
            'diagnosed_at' => now()->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->assertSame('pendiente', $diagnosis->fresh()->review_status);

        $this->post(route('admin.reviews.store'), [
            'reviewable_type' => 'patient_diagnosis',
            'reviewable_id' => $diagnosis->id,
            'decision' => 'autorizado',
        ])->assertSessionHasNoErrors();
        $this->assertSame('autorizado', $diagnosis->fresh()->review_status);
        $this->assertCount(2, $diagnosis->reviews);
    }

    public function test_pending_reviews_page_lists_by_teacher(): void
    {
        $teacherPeople = People::create(['first_name' => 'Doc', 'paternal_surname' => 'Uno']);
        $teacher = TeacherProfile::create(['people_id' => $teacherPeople->id]);
        $otherPeople = People::create(['first_name' => 'Doc', 'paternal_surname' => 'Dos']);
        $other = TeacherProfile::create(['people_id' => $otherPeople->id]);

        $this->diagnose(['teacher_profile_id' => $teacher->id]);
        $this->diagnose(['teacher_profile_id' => $other->id, 'tooth' => '11']);
        $this->diagnose(['teacher_profile_id' => null, 'tooth' => '21']);

        // El rol docente ya no revisa (solo consulta); la revisión es de un rol con ese permiso.
        $reviewer = Role::firstOrCreate(['name' => 'revisor_clinico', 'guard_name' => 'web'])
            ->givePermissionTo(['reviews.view', 'reviews.create']);
        $user = User::factory()->create()->assignRole($reviewer);
        $teacherPeople->update(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('admin.reviews.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reviews/Index')
                ->where('filters.scope', 'mine')
                ->has('items', 2)); // el suyo y el que no tiene docente asignado

        $this->actingAs($user)
            ->get(route('admin.reviews.index', ['scope' => 'all']))
            ->assertInertia(fn (Assert $page) => $page->has('items', 3));

        // Un alumno no puede revisar.
        $student = User::factory()->create()->assignRole('alumno');
        $this->actingAs($student)
            ->post(route('admin.reviews.store'), [
                'reviewable_type' => 'patient_diagnosis',
                'reviewable_id' => PatientDiagnosis::first()->id,
                'decision' => 'autorizado',
            ])->assertForbidden();
    }

    public function test_odontogram_entries_keep_history(): void
    {
        $this->post(route('admin.patients.odontogram.store', $this->patient), [
            'tooth' => '36',
            'entries' => [
                ['surface' => 'O', 'condition' => 'caries'],
                ['surface' => 'M', 'condition' => 'caries'],
            ],
        ])->assertSessionHasNoErrors();

        $this->post(route('admin.patients.odontogram.store', $this->patient), [
            'tooth' => '36',
            'entries' => [['surface' => 'O', 'condition' => 'obturado']],
        ])->assertSessionHasNoErrors();

        $this->assertSame(3, $this->patient->odontogramEntries()->count());
        $this->assertSame('obturado', $this->patient->odontogramEntries()->where('surface', 'O')->first()->condition);

        // Condición de diente completo usada en una cara.
        $this->post(route('admin.patients.odontogram.store', $this->patient), [
            'tooth' => '36',
            'entries' => [['surface' => 'O', 'condition' => 'ausente']],
        ])->assertSessionHasErrors('entries.0.condition');

        $this->get(route('admin.patients.show', $this->patient))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('patient.odontogram_entries', 3));

        $this->get(route('admin.diagnoses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('diagnoses', Diagnosis::count()));
    }
}
