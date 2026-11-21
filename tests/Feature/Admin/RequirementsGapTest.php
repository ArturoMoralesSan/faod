<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicActivity;
use App\Models\AcademicProgram;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Cohort;
use App\Models\Patient;
use App\Models\People;
use App\Models\Specialty;
use App\Models\StudentEnrollment;
use App\Models\StudentProfile;
use App\Models\StudyPlan;
use App\Models\TeacherProfile;
use App\Models\TreatmentPlan;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Puntos del documento de requerimientos que se completaron el 19 de noviembre de 2026.
 */
class RequirementsGapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->create()->assignRole('SuperAdmin'));
    }

    private function person(string $name, string $surname): People
    {
        return People::create(['first_name' => $name, 'paternal_surname' => $surname]);
    }

    private function patient(string $name): Patient
    {
        return Patient::create(['people_id' => $this->person($name, 'Paciente')->id, 'registered_at' => now()]);
    }

    /** 5.5, 5.6, 5.13 y 5.15: jornadas, objetivos, evaluación interna y resultados. */
    public function test_activity_objectives_evaluation_and_results(): void
    {
        $this->post(route('admin.activities.store'), [
            'type' => 'jornada',
            'title' => 'Jornada de salud bucal',
            'objectives' => 'Concientizar sobre higiene oral.',
            'modality' => 'presencial',
            'starts_at' => now()->toDateString(),
            'status' => 'concluida',
            'issues_certificate' => false,
            'min_attendance' => 0,
            'evaluation_score' => 9.5,
            'evaluation_notes' => 'Buena participación.',
            'results' => '120 pacientes revisados.',
        ])->assertSessionHasNoErrors();

        $activity = AcademicActivity::latest('id')->first();
        $this->assertSame('jornada', $activity->type);
        $this->assertSame(9.5, $activity->evaluation_score);
        $this->assertSame('120 pacientes revisados.', $activity->results);

        $this->put(route('admin.activities.update', $activity), [
            ...$activity->only('type', 'title', 'modality', 'status', 'min_attendance'),
            'starts_at' => now()->toDateString(),
            'evaluation_score' => 11,
        ])->assertSessionHasErrors('evaluation_score');
    }

    /** 9.13: indicaciones para el paciente en el plan y en el procedimiento. */
    public function test_patient_instructions_in_treatment(): void
    {
        $patient = $this->patient('Rosa');

        $this->post(route('admin.patients.treatment-plans.store', $patient), [
            'title' => 'Rehabilitación',
            'status' => 'en_proceso',
            'started_at' => now()->toDateString(),
            'patient_instructions' => 'Cepillado 3 veces al día.',
        ])->assertRedirect();

        $plan = TreatmentPlan::latest('id')->first();
        $this->assertSame('Cepillado 3 veces al día.', $plan->patient_instructions);

        $this->post(route('admin.treatment-plans.procedures.store', $plan), [
            'description' => 'Extracción 48',
            'status' => 'planeado',
            'patient_instructions' => 'No escupir en 24 h.',
        ])->assertSessionHasNoErrors();

        $this->assertSame('No escupir en 24 h.', $plan->procedures()->first()->patient_instructions);
    }

    /** 2.13: fotografía de la persona, en disco privado. */
    public function test_person_photo_upload_show_and_delete(): void
    {
        Storage::fake('local');
        $person = $this->person('Pedro', 'Ruiz');

        $this->post(route('admin.people.photo.update', $person), ['photo' => UploadedFile::fake()->image('foto.jpg', 300, 400)])
            ->assertSessionHasNoErrors();

        $path = $person->fresh()->photo_path;
        Storage::disk('local')->assertExists($path);
        $this->get(route('admin.people.photo.show', $person))->assertOk();

        // Muy pequeña o que no es imagen.
        $this->post(route('admin.people.photo.update', $person), ['photo' => UploadedFile::fake()->image('mini.jpg', 50, 50)])
            ->assertSessionHasErrors('photo');
        $this->post(route('admin.people.photo.update', $person), ['photo' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('photo');

        // Sin permiso no se ve.
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.people.photo.show', $person))->assertForbidden();

        $this->actingAs(User::factory()->create()->assignRole('SuperAdmin'));
        $this->delete(route('admin.people.photo.destroy', $person))->assertRedirect();
        $this->assertNull($person->fresh()->photo_path);
        Storage::disk('local')->assertMissing($path);
    }

    /** 6.13: pacientes por alumno que los atendió y por especialidad del alumno. */
    public function test_patients_filtered_by_student_and_specialty(): void
    {
        $ortho = Specialty::create(['name' => 'Ortodoncia', 'code' => 'ORT']);
        $endo = Specialty::create(['name' => 'Endodoncia', 'code' => 'END']);

        $student = function (string $number, Specialty $specialty): StudentProfile {
            $profile = StudentProfile::create(['people_id' => $this->person('Alumno', $number)->id, 'registration_number' => $number]);
            $program = AcademicProgram::create(['code' => "P{$number}", 'name' => "Especialidad {$specialty->name}", 'type' => 'especialidad', 'specialty_id' => $specialty->id, 'duration_value' => 2, 'duration_unit' => 'anios']);
            $cohort = Cohort::create(['academic_program_id' => $program->id, 'name' => "G{$number}", 'starts_at' => now()->toDateString()]);
            $plan = StudyPlan::create(['academic_program_id' => $program->id, 'name' => 'Plan', 'version' => '1']);
            StudentEnrollment::create(['student_profile_id' => $profile->id, 'academic_program_id' => $program->id, 'cohort_id' => $cohort->id, 'study_plan_id' => $plan->id, 'status' => 'activo']);

            return $profile;
        };

        $orthoStudent = $student('A-1', $ortho);
        $endoStudent = $student('A-2', $endo);
        $clinic = Clinic::create(['code' => 'CL', 'name' => 'Clínica']);

        $seenInAppointment = $this->patient('Cita');
        Appointment::create(['patient_id' => $seenInAppointment->id, 'clinic_id' => $clinic->id, 'student_profile_id' => $orthoStudent->id, 'date' => today(), 'start_time' => '09:00', 'end_time' => '10:00']);

        $seenInPlan = $this->patient('Plan');
        TreatmentPlan::create(['patient_id' => $seenInPlan->id, 'title' => 'Plan', 'status' => 'en_proceso', 'review_status' => 'pendiente', 'started_at' => today(), 'student_profile_id' => $endoStudent->id]);

        $this->patient('Nadie');

        $this->get(route('admin.patients.index', ['student_profile_id' => $orthoStudent->id]))
            ->assertInertia(fn (Assert $page) => $page->has('patients.data', 1)->where('patients.data.0.id', $seenInAppointment->id)->has('students', 2));

        $this->get(route('admin.patients.index', ['specialty_id' => $endo->id]))
            ->assertInertia(fn (Assert $page) => $page->has('patients.data', 1)->where('patients.data.0.id', $seenInPlan->id));

        $this->get(route('admin.patients.index'))
            ->assertInertia(fn (Assert $page) => $page->has('patients.data', 3));
    }

    /** 10.12, 10.13 y 10.14: agenda del día, del mes y filtro por docente. */
    public function test_agenda_day_month_and_teacher_filter(): void
    {
        $clinic = Clinic::create(['code' => 'CL', 'name' => 'Clínica']);
        $teacher = TeacherProfile::create(['people_id' => $this->person('Docente', 'Uno')->id, 'status' => 'active']);
        $patient = $this->patient('Ana');

        $make = fn ($date, $teacherId = null) => Appointment::create([
            'patient_id' => $patient->id, 'clinic_id' => $clinic->id, 'teacher_profile_id' => $teacherId,
            'date' => $date, 'start_time' => '09:00', 'end_time' => '10:00',
        ]);

        $make(today()->startOfMonth()->addDays(1), $teacher->id);
        $make(today()->startOfMonth()->addDays(10));
        $make(today()->addMonths(2));

        $day = today()->startOfMonth()->addDays(1)->toDateString();

        $this->get(route('admin.appointments.index', ['view' => 'day', 'date' => $day]))
            ->assertInertia(fn (Assert $page) => $page->where('view', 'day')->where('date', $day)->has('appointments', 1));

        $this->get(route('admin.appointments.index', ['view' => 'month', 'date' => $day]))
            ->assertInertia(fn (Assert $page) => $page->where('view', 'month')->has('appointments', 2)->has('teachers', 1));

        $this->get(route('admin.appointments.index', ['view' => 'month', 'date' => $day, 'teacher_profile_id' => $teacher->id]))
            ->assertInertia(fn (Assert $page) => $page->has('appointments', 1));

        // Vista o fecha inválidas no truenan.
        $this->get(route('admin.appointments.index', ['view' => 'x', 'date' => 'no-es-fecha']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('view', 'week'));
    }

    private function studentProfile(string $number = 'A-9'): StudentProfile
    {
        $profile = StudentProfile::create(['people_id' => $this->person('Alumno', $number)->id, 'registration_number' => $number]);
        $program = AcademicProgram::create(['code' => "P{$number}", 'name' => 'Programa', 'type' => 'especialidad', 'duration_value' => 2, 'duration_unit' => 'anios']);
        $cohort = Cohort::create(['academic_program_id' => $program->id, 'name' => 'G1', 'starts_at' => now()->toDateString()]);
        $plan = StudyPlan::create(['academic_program_id' => $program->id, 'name' => 'Plan', 'version' => '1']);
        StudentEnrollment::create(['student_profile_id' => $profile->id, 'academic_program_id' => $program->id, 'cohort_id' => $cohort->id, 'study_plan_id' => $plan->id, 'status' => 'activo']);

        return $profile;
    }

    /** 11.12, 11.14, 11.15 y 11.17: entregas a alumnos, devoluciones, mermas y dañados. */
    public function test_inventory_delivery_return_and_losses(): void
    {
        $warehouse = \App\Models\Warehouse::firstWhere('code', 'ALM');
        $item = \App\Models\InventoryItem::create(['code' => 'RES', 'name' => 'Resina', 'unit' => 'jeringa', 'tracks_expiration' => true, 'kind' => 'material']);
        $student = $this->studentProfile();
        $move = fn (array $data) => $this->post(route('admin.inventory.movements.store', $item), ['warehouse_id' => $warehouse->id, ...$data]);

        $move(['operation' => 'entrada', 'quantity' => 10, 'lot_number' => 'L1', 'expires_at' => now()->addYear()->toDateString()])->assertSessionHasNoErrors();
        $lot = $item->lots()->first();

        // Entrega: exige alumno.
        $move(['operation' => 'entrega', 'quantity' => 3])->assertSessionHasErrors('student_profile_id');
        $move(['operation' => 'entrega', 'quantity' => 3, 'student_profile_id' => $student->id, 'reason' => 'Práctica'])->assertSessionHasNoErrors();
        $this->assertEquals(7, $lot->fresh()->quantity);

        // Devolución al mismo lote (no pide caducidad porque la toma del lote).
        $move(['operation' => 'devolucion', 'quantity' => 1, 'inventory_lot_id' => $lot->id, 'student_profile_id' => $student->id, 'reason' => 'Sobrante'])->assertSessionHasNoErrors();
        $this->assertEquals(8, $lot->fresh()->quantity);

        // Merma y dañado: salidas con motivo obligatorio.
        $move(['operation' => 'merma', 'quantity' => 1])->assertSessionHasErrors('reason');
        $move(['operation' => 'merma', 'quantity' => 1, 'reason' => 'Se derramó'])->assertSessionHasNoErrors();
        $move(['operation' => 'danado', 'quantity' => 2, 'reason' => 'Contaminada'])->assertSessionHasNoErrors();
        $this->assertEquals(5, $lot->fresh()->quantity);

        $types = $item->movements()->reorder()->orderBy('id')->pluck('type')->all();
        $this->assertSame(['entrada', 'entrega', 'devolucion', 'merma', 'danado'], $types);
        $this->assertSame(2, $item->movements()->where('student_profile_id', $student->id)->count());

        $this->get(route('admin.inventory.movements.index', ['student_profile_id' => $student->id]))
            ->assertInertia(fn (Assert $page) => $page->where('movements.total', 2));
    }

    /** 11.4, 11.2 y 11.11: categorías del catálogo, tipo de artículo y almacén de laboratorio. */
    public function test_inventory_catalog_categories_kind_and_lab_warehouse(): void
    {
        $this->seed(\Database\Seeders\CatalogSeeder::class);

        $this->post(route('admin.inventory.store'), ['code' => 'X1', 'name' => 'Pinza', 'unit' => 'pza', 'minimum_stock' => 0, 'category' => 'Inventada'])
            ->assertSessionHasErrors('category');
        $this->post(route('admin.inventory.store'), ['code' => 'X1', 'name' => 'Pinza', 'unit' => 'pza', 'minimum_stock' => 0, 'category' => 'Instrumental', 'kind' => 'instrumental'])
            ->assertSessionHasNoErrors();
        $this->assertSame('instrumental', \App\Models\InventoryItem::firstWhere('code', 'X1')->kind);

        $lab = \App\Models\Space::create(['type' => 'laboratorio', 'code' => 'LAB1', 'name' => 'Laboratorio de prótesis']);
        $classroom = \App\Models\Space::create(['type' => 'aula', 'code' => 'A1', 'name' => 'Aula 1']);

        $this->post(route('admin.warehouses.store'), ['code' => 'LP', 'name' => 'Almacén de prótesis', 'space_id' => $classroom->id])->assertSessionHasErrors('space_id');
        $this->post(route('admin.warehouses.store'), ['code' => 'LP', 'name' => 'Almacén de prótesis', 'space_id' => $lab->id])->assertSessionHasNoErrors();
        $this->assertSame($lab->id, \App\Models\Warehouse::firstWhere('code', 'LP')->space_id);
    }

    /** 7.5, 9.4, 8.2 y 8.4: especialidad en catálogos y áreas; equipo o instrumental. */
    public function test_specialties_in_catalogs_areas_and_equipment_kind(): void
    {
        $endo = Specialty::create(['name' => 'Endodoncia', 'code' => 'END']);

        $this->post(route('admin.diagnoses.store'), ['code' => 'ZZ-01', 'name' => 'Pulpitis', 'specialty_id' => $endo->id, 'is_active' => true])->assertSessionHasNoErrors();
        $this->post(route('admin.diagnoses.store'), ['code' => 'ZZ-02', 'name' => 'Caries de dentina', 'is_active' => true])->assertSessionHasNoErrors();
        $this->post(route('admin.procedures.store'), ['code' => 'EN-01', 'name' => 'Endodoncia unirradicular', 'specialty_id' => $endo->id, 'is_active' => true])->assertSessionHasNoErrors();

        $this->get(route('admin.diagnoses.index', ['specialty_id' => $endo->id]))
            ->assertInertia(fn (Assert $page) => $page->has('diagnoses', 1)->where('diagnoses.0.specialty.name', 'Endodoncia'));
        $this->get(route('admin.procedures.index', ['specialty_id' => $endo->id]))
            ->assertInertia(fn (Assert $page) => $page->has('procedures', 1));

        $clinic = Clinic::create(['code' => 'CL', 'name' => 'Clínica']);
        $this->post(route('admin.clinics.records.store', [$clinic, 'areas']), ['name' => 'Endodoncia', 'specialty_id' => $endo->id, 'is_active' => true])->assertSessionHasNoErrors();
        $this->post(route('admin.clinics.records.store', [$clinic, 'equipment']), ['kind' => 'instrumental', 'code' => 'INS-1', 'name' => 'Kit de limas', 'status' => 'operativo'])->assertSessionHasNoErrors();
        $this->post(route('admin.clinics.records.store', [$clinic, 'equipment']), ['kind' => 'otro', 'code' => 'INS-2', 'name' => 'X', 'status' => 'operativo'])->assertSessionHasErrors('kind');

        $this->assertSame($endo->id, $clinic->areas()->first()->specialty_id);
        $this->assertSame('instrumental', \App\Models\ClinicEquipment::firstWhere('code', 'INS-1')->kind);
    }

    /** 8.13: historial de uso de un consultorio. */
    public function test_space_usage_history(): void
    {
        $clinic = Clinic::create(['code' => 'CL', 'name' => 'Clínica']);
        $space = \App\Models\Space::create(['clinic_id' => $clinic->id, 'type' => 'consultorio', 'code' => 'C1', 'name' => 'Consultorio 1']);
        $patient = $this->patient('Uso');

        Appointment::create(['patient_id' => $patient->id, 'clinic_id' => $clinic->id, 'space_id' => $space->id, 'date' => today()->subDays(3), 'start_time' => '09:00', 'end_time' => '10:00', 'status' => 'atendida']);
        Appointment::create(['patient_id' => $patient->id, 'clinic_id' => $clinic->id, 'space_id' => $space->id, 'date' => today()->subDays(200), 'start_time' => '09:00', 'end_time' => '10:00']);
        \App\Models\ClinicIncident::create(['clinic_id' => $clinic->id, 'space_id' => $space->id, 'type' => 'equipo', 'title' => 'Falla de unidad', 'description' => 'No enciende', 'occurred_at' => now()->subDay()]);

        $this->get(route('admin.spaces.show', $space))
            ->assertInertia(fn (Assert $page) => $page
                ->has('usage', 2)
                ->where('usage.0.kind', 'incidencia')
                ->where('usage.1.kind', 'cita')
                ->where('usage.1.status', 'atendida'));
    }

    /** 4.12: participación académica del docente. */
    public function test_teacher_participation_history(): void
    {
        $teacher = TeacherProfile::create(['people_id' => $this->person('Docente', 'Dos')->id, 'status' => 'active']);
        $activity = AcademicActivity::create(['type' => 'curso', 'title' => 'Curso de oclusión', 'modality' => 'presencial', 'starts_at' => today(), 'status' => 'concluida', 'min_attendance' => 80]);
        $activity->instructors()->create(['teacher_profile_id' => $teacher->id, 'role' => 'Ponente']);
        AcademicActivity::create(['type' => 'taller', 'title' => 'Taller coordinado', 'modality' => 'presencial', 'starts_at' => today(), 'status' => 'planeada', 'min_attendance' => 80, 'coordinator_id' => $teacher->id]);

        $clinic = Clinic::create(['code' => 'CL', 'name' => 'Clínica']);
        \App\Models\ClinicalSession::create(['clinic_id' => $clinic->id, 'supervisor_id' => $teacher->id, 'date' => today()->subDay(), 'start_time' => '08:00', 'end_time' => '10:00', 'status' => 'concluida']);

        $this->get(route('admin.teachers.show', $teacher))
            ->assertInertia(fn (Assert $page) => $page
                ->where('participation.stats.activities', 2)
                ->where('participation.stats.sessions', 1)
                ->where('participation.stats.sessions_done', 1)
                ->has('participation.recentSessions', 1)
                ->where('participation.activities', fn ($activities) => collect($activities)->pluck('role')->sort()->values()->all() === ['Coordinador', 'Ponente']));
    }

    /** 1.10: bitácora de accesos. */
    public function test_login_events_are_recorded(): void
    {
        $user = User::factory()->create(['email' => 'ana@faod.test', 'password' => bcrypt('secreta123')]);
        auth()->logout();
        \App\Models\LoginEvent::query()->delete();

        $this->post(route('login.store'), ['email' => 'ana@faod.test', 'password' => 'mala'])->assertSessionHasErrors();
        $this->post(route('login.store'), ['email' => 'ana@faod.test', 'password' => 'secreta123']);
        $this->post(route('logout'));

        $events = \App\Models\LoginEvent::orderBy('id')->get();
        $this->assertSame(['failed', 'login', 'logout'], $events->pluck('event')->all());
        $this->assertSame('ana@faod.test', $events[0]->email);
        $this->assertSame($user->id, $events[1]->user_id);

        $this->actingAs(User::factory()->create()->assignRole('SuperAdmin'));
        $this->get(route('admin.activity-log.logins', ['event' => 'failed']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('logs.total', 1)->where('failedToday', 1));
    }

    /** 12.4, 12.9, 12.11 y 12.12: cobros manuales sin adeudo, gastos de laboratorio y movimientos de caja. */
    public function test_manual_income_course_payment_lab_expense_and_cash_movements(): void
    {
        $register = \App\Models\CashRegister::create(['name' => 'Caja 1']);
        $this->post(route('admin.finance.cash-registers.open', $register), ['opening_amount' => 500])->assertRedirect();
        $session = \App\Models\CashRegisterSession::first();

        // Otro ingreso: quién paga y concepto obligatorios; en efectivo exige caja.
        $this->post(route('admin.finance.incomes.store'), ['method' => 'efectivo', 'amount' => 200])
            ->assertSessionHasErrors(['payer_name', 'concept']);
        $this->post(route('admin.finance.incomes.store'), ['payer_name' => 'X', 'concept' => 'Y', 'method' => 'efectivo', 'amount' => 200])
            ->assertSessionHasErrors('cash_register_session_id');
        $this->post(route('admin.finance.incomes.store'), ['payer_name' => 'Fundación Sonrisas', 'concept' => 'Donativo', 'method' => 'efectivo', 'amount' => 200, 'cash_register_session_id' => $session->id])
            ->assertRedirect();

        $donation = \App\Models\Payment::latest('id')->first();
        $this->assertNull($donation->payer_type);
        $this->assertMatchesRegularExpression('/^R-\d{6}$/', $donation->folio);
        $this->get(route('admin.finance.payments.show', $donation))
            ->assertInertia(fn (Assert $page) => $page->where('payment.payer_info.type', 'externo')->where('payment.payer_info.name', 'Fundación Sonrisas'));

        // Cobro de la inscripción de un participante externo.
        $activity = AcademicActivity::create(['type' => 'congreso', 'title' => 'Congreso FAOD', 'modality' => 'presencial', 'starts_at' => today(), 'status' => 'abierta', 'min_attendance' => 80, 'cost' => 1500]);
        $participant = $activity->participants()->create(['people_id' => $this->person('Externa', 'Invitada')->id, 'payment_status' => 'pendiente']);

        $this->post(route('admin.finance.incomes.store'), ['activity_participant_id' => $participant->id, 'method' => 'tarjeta', 'amount' => 1500])->assertRedirect();
        $this->assertSame('pagado', $participant->fresh()->payment_status);
        $coursePayment = \App\Models\Payment::latest('id')->first();
        $this->assertSame('Inscripción: Congreso FAOD', $coursePayment->concept);
        $this->assertSame('Externa Invitada', $coursePayment->payer_name);

        // No se cobra dos veces; al cancelar el pago vuelve a quedar pendiente.
        $this->post(route('admin.finance.incomes.store'), ['activity_participant_id' => $participant->id, 'method' => 'tarjeta', 'amount' => 1500])
            ->assertSessionHasErrors('activity_participant_id');
        $this->patch(route('admin.finance.payments.cancel', $coursePayment), ['cancellation_reason' => 'Error'])->assertSessionHasNoErrors();
        $this->assertSame('pendiente', $participant->fresh()->payment_status);

        // Depósito y retiro de caja; no se retira más de lo que hay.
        $this->post(route('admin.finance.cash-sessions.movements.store', $session), ['type' => 'retiro', 'amount' => 5000, 'reason' => 'Banco'])->assertSessionHasErrors('amount');
        $this->post(route('admin.finance.cash-sessions.movements.store', $session), ['type' => 'deposito', 'amount' => 100, 'reason' => 'Cambio'])->assertSessionHasNoErrors();
        $this->post(route('admin.finance.cash-sessions.movements.store', $session), ['type' => 'retiro', 'amount' => 300, 'reason' => 'Banco'])->assertSessionHasNoErrors();

        // 500 fondo + 200 donativo + 100 depósito − 300 retiro.
        $this->assertEquals(500, $session->fresh()->summary()['expected_cash']);

        // Gasto de laboratorio.
        $lab = \App\Models\Space::create(['type' => 'laboratorio', 'code' => 'LAB1', 'name' => 'Laboratorio']);
        $this->post(route('admin.finance.expenses.store'), ['category' => 'material', 'description' => 'Yeso', 'amount' => 80, 'method' => 'transferencia', 'paid_at' => now()->format('Y-m-d H:i'), 'space_id' => $lab->id])
            ->assertSessionHasNoErrors();
        $this->assertSame($lab->id, \App\Models\Expense::first()->space_id);

        // Reporte de pagos: los externos se filtran aparte.
        $this->get(route('admin.reports.show', ['pagos', 'payer_type' => 'externo']))
            ->assertInertia(fn (Assert $page) => $page->where('rows.total', 2)->where('rows.data.0.cells.payer_type', 'Externo'));
    }
}
