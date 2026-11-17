<?php

namespace Tests\Feature\Admin;

use App\Models\Patient;
use App\Models\People;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->create()->assignRole('SuperAdmin'));
    }

    private function patientData(array $overrides = []): array
    {
        return [
            'first_name' => 'María',
            'paternal_surname' => 'López',
            'maternal_surname' => 'Ruiz',
            'birth_date' => '1990-05-10',
            'phone' => '6181234567',
            'status' => 'activo',
            'registered_at' => now()->toDateString(),
            ...$overrides,
        ];
    }

    public function test_patient_is_created_with_consecutive_record_number(): void
    {
        $this->post(route('admin.patients.store'), $this->patientData())->assertSessionHasNoErrors();
        $this->post(route('admin.patients.store'), $this->patientData(['first_name' => 'Juan']))->assertSessionHasNoErrors();

        $this->assertSame(['P-000001', 'P-000002'], Patient::orderBy('id')->pluck('record_number')->all());
        $this->assertDatabaseHas('people', ['first_name' => 'María', 'paternal_surname' => 'López']);
    }

    public function test_existing_person_is_reused_by_curp(): void
    {
        $people = People::create(['first_name' => 'Ana', 'paternal_surname' => 'Alumna', 'curp' => 'AAAA900101MDGLLN01']);
        StudentProfile::create(['people_id' => $people->id, 'registration_number' => 'A100']);

        $this->post(route('admin.patients.store'), $this->patientData([
            'first_name' => 'Ana',
            'paternal_surname' => 'Alumna',
            'curp' => 'aaaa900101mdglln01',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(1, People::count());
        $this->assertSame($people->id, Patient::first()->people_id);
        // Se completan datos que no tenía, sin cambiar los existentes.
        $this->assertSame('6181234567', $people->fresh()->phone);

        // La misma persona no puede tener dos expedientes.
        $this->post(route('admin.patients.store'), $this->patientData(['curp' => 'AAAA900101MDGLLN01']))
            ->assertSessionHasErrors('curp');
    }

    public function test_record_entries_and_pages(): void
    {
        $this->post(route('admin.patients.store'), $this->patientData());
        $patient = Patient::first();

        $entries = [
            'contacts' => ['name' => 'Pedro López', 'relationship' => 'Padre', 'phone' => '6180000000'],
            'conditions' => ['category' => 'patologico', 'name' => 'Hipertensión'],
            'allergies' => ['allergen' => 'Penicilina', 'severity' => 'grave'],
            'medications' => ['name' => 'Losartán', 'dose' => '50 mg'],
            'consultations' => [
                'attended_at' => now()->subHour()->format('Y-m-d\TH:i'),
                'reason' => 'Dolor en molar inferior',
                'blood_pressure' => '120/80',
                'heart_rate' => 72,
            ],
        ];

        foreach ($entries as $type => $data) {
            $this->post(route('admin.patients.records.store', [$patient, $type]), $data)->assertSessionHasNoErrors();
        }

        $consultation = $patient->consultations()->first();
        $this->post(route('admin.patients.records.store', [$patient, 'notes']), [
            'patient_consultation_id' => $consultation->id,
            'noted_at' => now()->format('Y-m-d\TH:i'),
            'note' => 'Se indica radiografía periapical.',
        ])->assertSessionHasNoErrors();

        $this->assertNotNull($consultation->created_by);

        // Tensión arterial con formato inválido.
        $this->post(route('admin.patients.records.store', [$patient, 'consultations']), [
            ...$entries['consultations'],
            'blood_pressure' => 'alta',
        ])->assertSessionHasErrors('blood_pressure');

        // Una consulta de otro paciente no se puede ligar a la nota.
        $this->post(route('admin.patients.store'), $this->patientData(['first_name' => 'Otro']));
        $other = Patient::latest('id')->first();
        $this->post(route('admin.patients.records.store', [$other, 'notes']), [
            'patient_consultation_id' => $consultation->id,
            'noted_at' => now()->format('Y-m-d\TH:i'),
            'note' => 'X',
        ])->assertSessionHasErrors('patient_consultation_id');

        $this->get(route('admin.patients.show', $patient))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/patients/Show')
                ->has('patient.allergies', 1)
                ->has('patient.consultations', 1)
                ->has('patient.progress_notes', 1));

        $this->get(route('admin.patients.index', ['search' => 'maría lópez']))
            ->assertInertia(fn (Assert $page) => $page->has('patients.data', 1));

        $this->get(route('admin.patients.edit', $patient))->assertOk();
    }

    public function test_patient_update_changes_person_data(): void
    {
        $this->post(route('admin.patients.store'), $this->patientData());
        $patient = Patient::first();

        $this->put(route('admin.patients.update', $patient), $this->patientData(['phone' => '6189999999', 'status' => 'alta']))
            ->assertSessionHasNoErrors();

        $this->assertSame('6189999999', $patient->people->fresh()->phone);
        $this->assertSame('alta', $patient->fresh()->status);
    }
}
