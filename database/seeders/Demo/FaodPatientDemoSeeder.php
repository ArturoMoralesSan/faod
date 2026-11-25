<?php

namespace Database\Seeders\Demo;

use App\Models\AcademicPeriod;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicSupervisor;
use App\Models\Diagnosis;
use App\Models\InventoryItem;
use App\Models\Patient;
use App\Models\PatientConsultation;
use App\Models\PatientDiagnosis;
use App\Models\Procedure;
use App\Models\Review;
use App\Models\Space;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\TreatmentPlan;
use App\Models\TreatmentProcedure;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;

/**
 * Pacientes de las clínicas con historia clínica, consulta de primera vez,
 * diagnósticos revisados por el docente, odontograma, plan de tratamiento,
 * notas de evolución, consumo de material y citas (pasadas y próximas).
 */
class FaodPatientDemoSeeder extends Seeder
{
    use DemoHelpers;

    /** Clínica => [pacientes, edad mínima, edad máxima, programa de los alumnos que atienden]. */
    private const CLINICS = [
        'CLIN-LIC' => [26, 15, 75, 'LCD'],
        'CLIN-PERIO' => [7, 35, 70, 'EPI'],
        'CLIN-ORTO' => [7, 10, 28, 'EORT'],
        'CLIN-ODP' => [8, 4, 12, 'EODP'],
    ];

    private const POSTERIOR = [14, 15, 16, 17, 24, 25, 26, 27, 34, 35, 36, 37, 44, 45, 46, 47];

    private const ANTERIOR = [11, 12, 13, 21, 22, 23, 31, 32, 33, 41, 42, 43];

    private const MOLARS = [16, 17, 26, 27, 36, 37, 46, 47];

    private const PRIMARY_MOLARS = [54, 55, 64, 65, 74, 75, 84, 85];

    /**
     * Hallazgos por clínica: [diagnóstico CIE-10, dientes, superficie, condición en odontograma, procedimientos].
     * Procedimiento: clave del catálogo, o [descripción, costo] si no está en el catálogo.
     */
    private const FINDINGS = [
        'CLIN-LIC' => [
            ['K02.1', self::POSTERIOR, 'O', 'caries', ['OP-01']],
            ['K02.1', self::POSTERIOR, 'O', 'caries', ['OP-01']],
            ['K02.0', self::MOLARS, 'O', 'caries', ['PRV-03']],
            ['K02.1', self::ANTERIOR, 'M', 'caries', ['OP-01']],
            ['K04.0', self::MOLARS, null, 'caries', ['END-03', 'PRO-01']],
            ['K04.1', self::ANTERIOR, null, 'caries', ['END-02']],
            ['K05.1', null, null, null, ['PRV-01']],
            ['K05.3', null, null, null, ['PER-01', 'PER-01']],
            ['K08.3', self::POSTERIOR, null, 'extraccion_indicada', ['CIR-01']],
            ['K01.1', [38, 48], null, 'extraccion_indicada', ['DX-03', 'CIR-02']],
            ['K08.1', [36, 46], null, 'ausente', ['PRO-02']],
        ],
        'CLIN-PERIO' => [
            ['K05.3', null, null, null, ['PER-01', 'PER-01', 'PER-01']],
            ['K06.0', self::ANTERIOR, null, null, [['Injerto de tejido conectivo', 4500]]],
            ['K08.1', [36, 46, 16, 26], null, 'ausente', [['Colocación de implante dental', 12000]]],
            ['K05.1', null, null, null, ['PRV-01']],
        ],
        'CLIN-ORTO' => [
            ['K07.2', null, null, null, ['DX-03', ['Colocación de aparatología fija', 9000], ['Control mensual de ortodoncia', 600], ['Control mensual de ortodoncia', 600]]],
            ['K07.3', self::ANTERIOR, null, null, [['Colocación de aparatología fija', 9000], ['Control mensual de ortodoncia', 600]]],
            ['K05.1', null, null, null, ['PRV-01']],
        ],
        'CLIN-ODP' => [
            ['K02.1', self::PRIMARY_MOLARS, 'O', 'caries', ['OP-03']],
            ['K02.1', self::PRIMARY_MOLARS, 'O', 'caries', ['OP-03']],
            ['K04.0', self::PRIMARY_MOLARS, null, 'caries', ['END-01', ['Corona de acero cromo', 800]]],
            ['K02.0', [16, 26, 36, 46], 'O', 'caries', ['PRV-03']],
            ['K05.1', null, null, null, ['PRV-01', 'PRV-02']],
        ],
    ];

    /** Material que se descuenta del almacén al realizar el procedimiento: [artículo, cantidad]. */
    private const MATERIALS = [
        'OP-01' => [['RES-001', 0.2], ['RES-003', 0.05], ['ANE-001', 0.04]],
        'OP-03' => [['CEM-001', 0.1], ['ANE-001', 0.04]],
        'END-01' => [['ANE-001', 0.04], ['CEM-002', 0.1]],
        'END-02' => [['ANE-001', 0.04], ['END-001', 0.5], ['END-002', 0.2], ['END-003', 0.1]],
        'END-03' => [['ANE-001', 0.06], ['END-001', 1], ['END-002', 0.3], ['END-003', 0.15]],
        'PRV-01' => [['PRF-001', 0.05]],
        'PRV-02' => [['PRF-002', 0.05]],
        'PRV-03' => [['PRF-003', 0.1], ['RES-004', 0.05]],
        'PER-01' => [['ANE-001', 0.04]],
        'CIR-01' => [['ANE-001', 0.04], ['CIR-001', 0.1]],
        'CIR-02' => [['ANE-001', 0.08], ['CIR-001', 0.1], ['CIR-002', 0.02]],
    ];

    private const REASONS = [
        'CLIN-LIC' => ['Revisión general y limpieza', 'Dolor en muela inferior al comer', 'Tiene "picaduras" en varios dientes', 'Le sangran las encías al cepillarse', 'Se le rompió un diente', 'Quiere reponer un diente que le falta'],
        'CLIN-PERIO' => ['Encías inflamadas y sangrado', 'Movilidad de dientes inferiores', 'Le recomendaron implante', 'Mal aliento persistente'],
        'CLIN-ORTO' => ['Dientes encimados', 'Mordida abierta', 'Valoración para brackets', 'Dientes separados'],
        'CLIN-ODP' => ['Revisión de primera vez', 'Dolor en muela al comer dulces', 'Manchas negras en los dientes', 'Golpe en diente de enfrente'],
    ];

    private InventoryService $inventory;

    private ?User $receptionist;

    /** @var array<string, true> ocupación de citas: espacio, alumno y paciente por fecha y hora */
    private array $busy = [];

    public function run(InventoryService $inventory): void
    {
        $this->inventory = $inventory;
        $this->receptionist = User::whereHas('people.staffProfile', fn ($q) => $q->where('position', 'Recepcionista de clínicas'))->first();
        $period = AcademicPeriod::current();

        foreach (self::CLINICS as $code => [$count, $minAge, $maxAge, $program]) {
            $clinic = Clinic::firstWhere('code', $code);
            $units = Space::where('clinic_id', $clinic->id)->where('type', 'consultorio')->orderBy('code')->get();
            $students = StudentProfile::query()
                ->where('status', 'active')
                ->whereHas('groups', fn ($q) => $q->where('academic_period_id', $period->id)
                    ->where('code', 'like', "{$program}-%")
                    ->when($program === 'LCD', fn ($q) => $q->where('period_number', '>=', 5)))
                ->with('people')
                ->get();
            $teachers = TeacherProfile::whereIn('id', ClinicSupervisor::where('clinic_id', $clinic->id)->pluck('teacher_profile_id'))
                ->with('people')
                ->get();
            $warehouse = Warehouse::firstWhere('code', $code === 'CLIN-LIC' ? 'ALM-CLIC' : 'ALM-POS');

            for ($n = 0; $n < $count; $n++) {
                $this->patient($clinic, $units, $students[$n % $students->count()], $teachers->random(), $warehouse, $minAge, $maxAge, $period);
            }
        }

        Auth::forgetUser();
    }

    private function patient(Clinic $clinic, Collection $units, StudentProfile $student, TeacherProfile $teacher, ?Warehouse $warehouse, int $minAge, int $maxAge, AcademicPeriod $period): void
    {
        $person = $this->makePerson(null, [], $minAge, $maxAge);
        $age = Date::parse($person->birth_date)->age;
        $child = $age < 15;
        $studentUser = $student->people->user_id;

        $earliest = $period->starts_at->copy()->addDays(5);
        $registered = $this->weekday(today()->subDays(mt_rand(10, 110))->max($earliest));

        if ($registered->gte(today())) {
            $registered = $this->weekday($earliest);
        }

        $patient = Patient::create([
            'people_id' => $person->id,
            'clinic_id' => $clinic->id,
            'blood_type' => $this->pick(['O+', 'O+', 'O+', 'A+', 'A+', 'B+', 'O-', 'AB+']),
            'occupation' => $child ? 'Estudiante' : $this->pick(['Empleado(a)', 'Ama de casa', 'Comerciante', 'Docente', 'Estudiante', 'Jubilado(a)', 'Chofer', 'Enfermero(a)', 'Agricultor', 'Contador(a)']),
            'marital_status' => $child || $age < 20 ? 'soltero' : $this->pick(['soltero', 'casado', 'casado', 'union_libre', 'divorciado', 'viudo']),
            'referred_by' => $this->pick(['Familiar', 'Redes sociales', 'Jornada de salud bucal', 'Otro paciente', 'Centro de salud', 'Alumno de la Facultad']),
            'status' => 'activo',
            'registered_at' => $registered->toDateString(),
            'created_by' => $this->receptionist?->id,
        ]);

        $this->history($patient, $age, $person->gender);

        // Consulta de primera vez.
        $attended = $registered->copy()->setTime(mt_rand(8, 12), 0);
        $symptoms = $this->pickMany(['dolor_provocado', 'dolor_al_masticar', 'sensibilidad_frio', 'sangrado_encias', 'inflamacion', 'halitosis', 'sensibilidad_dulce'], mt_rand(0, 3));
        $consultation = PatientConsultation::create([
            'patient_id' => $patient->id,
            'clinic_id' => $clinic->id,
            'student_profile_id' => $student->id,
            'teacher_profile_id' => $teacher->id,
            'attended_at' => $attended,
            'reason' => $this->pick(self::REASONS[$clinic->code]),
            'current_illness' => $symptoms ? 'Refiere molestias desde hace '.mt_rand(1, 8).' semanas.' : 'Asintomático; acude a revisión.',
            'symptoms' => $symptoms,
            'pain_intensity' => array_intersect($symptoms, ['dolor_provocado', 'dolor_al_masticar']) ? mt_rand(3, 8) : 0,
            'blood_pressure' => $child ? null : mt_rand(105, 140).'/'.mt_rand(65, 90),
            'heart_rate' => mt_rand(64, 96),
            'respiratory_rate' => mt_rand(14, 20),
            'temperature' => 36 + mt_rand(1, 8) / 10,
            'weight' => $child ? mt_rand(16, 42) : mt_rand(52, 98),
            'height' => $child ? mt_rand(100, 150) / 100 : mt_rand(150, 185) / 100,
            'findings' => 'Exploración intraoral y extraoral; se registran hallazgos en el odontograma.',
            'created_by' => $studentUser,
        ]);

        $this->appointment($patient, $clinic, $units, $student, $teacher, null, $attended, 'atendida', 'Valoración de primera vez');
        $this->baselineOdontogram($patient, $child, $attended, $studentUser);

        // Diagnósticos y procedimientos que se derivan de ellos.
        $findings = $this->pickMany(self::FINDINGS[$clinic->code], mt_rand(2, 4));
        $usedTeeth = [];
        $planned = [];

        foreach ($findings as [$code, $teeth, $surface, $condition, $procedures]) {
            $tooth = null;

            if ($teeth) {
                $free = array_values(array_diff($teeth, $usedTeeth)) ?: $teeth;
                $tooth = $this->pick($free);
                $usedTeeth[] = $tooth;
            }

            $diagnosis = PatientDiagnosis::create([
                'patient_id' => $patient->id,
                'patient_consultation_id' => $consultation->id,
                'diagnosis_id' => Diagnosis::firstWhere('code', $code)?->id,
                'tooth' => $tooth ? (string) $tooth : null,
                'surfaces' => $surface,
                'type' => $this->chance(80) ? 'definitivo' : 'presuntivo',
                'diagnosed_at' => $registered->toDateString(),
                'student_profile_id' => $student->id,
                'teacher_profile_id' => $teacher->id,
                'created_by' => $studentUser,
            ]);

            if ($tooth && $condition) {
                $patient->odontogramEntries()->create([
                    'patient_diagnosis_id' => $diagnosis->id,
                    'tooth' => (string) $tooth,
                    'surface' => $condition === 'caries' ? ($surface ?? 'O') : null,
                    'condition' => $condition,
                    'recorded_at' => $attended,
                    'created_by' => $studentUser,
                ]);
            }

            foreach ($procedures as $procedure) {
                $planned[] = [$diagnosis, $procedure, $tooth, $surface];
            }
        }

        $this->treatmentPlan($patient, $clinic, $units, $student, $teacher, $warehouse, $registered, $planned, $child);
    }

    private function history(Patient $patient, int $age, ?string $gender): void
    {
        $relative = $age < 18 ? $this->pick(['madre', 'padre', 'madre']) : $this->pick(['conyuge', 'hijo', 'hermano', 'madre']);
        $patient->emergencyContacts()->create([
            'name' => $this->fullName($relative === 'madre' ? 'femenino' : ($relative === 'padre' ? 'masculino' : null)),
            'relationship' => $relative,
            'phone' => $this->phone(),
        ]);

        if ($this->chance(40)) {
            $patient->conditions()->create(['category' => 'heredofamiliar', 'name' => 'Diabetes mellitus tipo 2', 'notes' => $this->pick(['Padre', 'Madre', 'Abuelos paternos'])]);
        }

        if ($this->chance(30)) {
            $patient->conditions()->create(['category' => 'heredofamiliar', 'name' => 'Hipertensión arterial', 'notes' => $this->pick(['Madre', 'Padre', 'Abuela materna'])]);
        }

        if ($age >= 40 && $this->chance(25)) {
            $patient->conditions()->create(['category' => 'patologico', 'name' => 'Hipertensión arterial', 'notes' => 'Controlada con medicamento.']);
            $patient->medications()->create(['name' => 'Losartán', 'dose' => '50 mg', 'frequency' => 'Cada 24 horas', 'started_at' => today()->subYears(mt_rand(1, 8))->toDateString()]);
        }

        if ($age >= 40 && $this->chance(18)) {
            $patient->conditions()->create(['category' => 'patologico', 'name' => 'Diabetes mellitus tipo 2', 'notes' => 'Última glucosa capilar: '.mt_rand(110, 180).' mg/dl.']);
            $patient->medications()->create(['name' => 'Metformina', 'dose' => '850 mg', 'frequency' => 'Cada 12 horas', 'started_at' => today()->subYears(mt_rand(1, 10))->toDateString()]);
        }

        if ($age < 18 && $this->chance(12)) {
            $patient->conditions()->create(['category' => 'patologico', 'name' => 'Asma', 'notes' => 'Crisis ocasionales.']);
            $patient->medications()->create(['name' => 'Salbutamol inhalado', 'dose' => '100 mcg', 'frequency' => 'En caso de crisis']);
        }

        $patient->conditions()->create([
            'category' => 'no_patologico',
            'name' => 'Higiene bucal',
            'notes' => 'Cepillado '.$this->pick(['1 vez', '2 veces', '3 veces']).' al día'.($this->chance(30) ? '; usa hilo dental.' : '; no usa hilo dental.'),
        ]);

        if ($age >= 18 && $this->chance(12)) {
            $patient->conditions()->create(['category' => 'no_patologico', 'name' => 'Tabaquismo', 'notes' => mt_rand(3, 15).' cigarros al día.']);
        }

        $patient->conditions()->create([
            'category' => 'odontologico',
            'name' => 'Última visita al dentista',
            'notes' => $this->pick(['Hace 6 meses', 'Hace 1 año', 'Hace 2 años', 'Hace más de 5 años', 'Primera visita']),
        ]);

        if ($gender === 'femenino' && $age >= 20 && $age <= 38 && $this->chance(6)) {
            $patient->conditions()->create(['category' => 'ginecoobstetrico', 'name' => 'Embarazo', 'notes' => mt_rand(14, 28).' semanas de gestación; valorar interconsulta.']);
        }

        if ($this->chance(15)) {
            [$allergen, $reaction, $severity] = $this->pick([
                ['Penicilina', 'Urticaria generalizada', 'moderada'],
                ['Látex', 'Dermatitis de contacto', 'leve'],
                ['Ácido acetilsalicílico', 'Broncoespasmo', 'grave'],
                ['Sulfas', 'Erupción cutánea', 'moderada'],
            ]);
            $patient->allergies()->create(['allergen' => $allergen, 'reaction' => $reaction, 'severity' => $severity]);
        }
    }

    /** Restauraciones y ausencias previas que trae el paciente. */
    private function baselineOdontogram(Patient $patient, bool $child, CarbonInterface $at, ?int $userId): void
    {
        if ($child) {
            return;
        }

        foreach ($this->pickMany(self::POSTERIOR, mt_rand(0, 3)) as $tooth) {
            $patient->odontogramEntries()->create([
                'tooth' => (string) $tooth, 'surface' => 'O', 'condition' => 'obturado',
                'notes' => 'Restauración previa de amalgama.', 'recorded_at' => $at, 'created_by' => $userId,
            ]);
        }

        if ($this->chance(25)) {
            $patient->odontogramEntries()->create([
                'tooth' => (string) $this->pick([18, 28, 38, 48]), 'condition' => 'ausente',
                'notes' => 'Extraído hace años.', 'recorded_at' => $at, 'created_by' => $userId,
            ]);
        }
    }

    private function treatmentPlan(Patient $patient, Clinic $clinic, Collection $units, StudentProfile $student, TeacherProfile $teacher, ?Warehouse $warehouse, CarbonInterface $registered, array $planned, bool $child): void
    {
        $studentUser = $student->people->user_id;
        $teacherUser = $teacher->people->user_id;

        $plan = TreatmentPlan::create([
            'patient_id' => $patient->id,
            'title' => match ($clinic->code) {
                'CLIN-PERIO' => 'Tratamiento periodontal integral',
                'CLIN-ORTO' => 'Tratamiento de ortodoncia',
                'CLIN-ODP' => 'Rehabilitación bucal pediátrica',
                default => 'Plan de tratamiento integral',
            },
            'notes' => 'Plan elaborado por el alumno y revisado por el docente supervisor.',
            'patient_instructions' => $child
                ? 'Cepillado supervisado por un adulto tres veces al día; reducir dulces entre comidas.'
                : 'Cepillado tres veces al día, uso de hilo dental y acudir puntual a sus citas.',
            'student_profile_id' => $student->id,
            'teacher_profile_id' => $teacher->id,
            'started_at' => $registered->toDateString(),
            'created_by' => $studentUser,
        ]);

        $stages = collect([
            [1, 'Diagnóstico', 'Historia clínica, estudios radiográficos y modelos.'],
            [2, 'Fase higiénica y preventiva', 'Control de placa, profilaxis y medidas preventivas.'],
            [3, 'Fase correctiva', 'Tratamientos restauradores, endodónticos, quirúrgicos o de especialidad.'],
            [4, 'Mantenimiento', 'Citas de control y refuerzo de higiene.'],
        ])->map(fn ($s) => $plan->stages()->create(['order' => $s[0], 'name' => $s[1], 'description' => $s[2]]))->values();

        $catalog = Procedure::all()->keyBy('code');
        $procedures = collect([[null, 'DX-01', null, null], ...$planned])
            ->map(function ($row) use ($catalog, $plan, $patient, $stages, $student, $teacher) {
                [$diagnosis, $procedure, $tooth, $surface] = $row;
                $model = is_string($procedure) ? $catalog[$procedure] ?? null : null;
                $category = $model?->category;

                return TreatmentProcedure::create([
                    'treatment_plan_id' => $plan->id,
                    'treatment_stage_id' => $stages[match (true) {
                        $category === 'Diagnóstico' => 0,
                        $category === 'Prevención', $model?->code === 'PER-01' => 1,
                        default => 2,
                    }]->id,
                    'patient_id' => $patient->id,
                    'procedure_id' => $model?->id,
                    'patient_diagnosis_id' => $diagnosis?->id,
                    'description' => $model ? null : $procedure[0],
                    'tooth' => $tooth ? (string) $tooth : null,
                    'surfaces' => $surface,
                    'status' => 'planeado',
                    'cost' => $model ? $model->default_cost : $procedure[1],
                    'student_profile_id' => $student->id,
                    'teacher_profile_id' => $teacher->id,
                ]);
            });

        // Avance: entre más antiguo el paciente, más procedimientos realizados.
        $days = (int) $registered->diffInDays(today());
        $done = $this->chance(6) ? 1 : min($procedures->count(), (int) round($procedures->count() * min(1, $days / 70)) + ($days > 20 ? 1 : 0));
        $date = $registered->copy();

        foreach ($procedures->take($done) as $i => $procedure) {
            if ($i > 0) {
                $date = $this->weekday($date->copy()->addDays(mt_rand(5, 12)));

                if ($date->gte(today())) {
                    break;
                }
            }

            $at = $i === 0 ? $registered->copy()->setTime(9, 30) : $date->copy()->setTime(mt_rand(8, 17), 0);

            if ($i > 0) {
                $at = $this->appointment($patient, $clinic, $units, $student, $teacher, $plan, $at, 'atendida', $procedure->procedure?->name ?? $procedure->description) ?? $at;
            }

            $procedure->update(['status' => 'realizado', 'performed_at' => $at]);
            $this->consumeMaterials($procedure, $warehouse, $studentUser);
            $this->afterProcedure($patient, $procedure, $at, $studentUser);

            if ($i > 0) {
                $patient->progressNotes()->create([
                    'student_profile_id' => $student->id,
                    'teacher_profile_id' => $teacher->id,
                    'noted_at' => $at->copy()->addMinutes(50),
                    'note' => sprintf('Se realiza %s%s sin complicaciones. Se dan indicaciones posoperatorias.', mb_strtolower($procedure->procedure?->name ?? $procedure->description), $procedure->tooth ? " en el diente {$procedure->tooth}" : ''),
                    'created_by' => $studentUser,
                ]);
            }

            if ($procedure->cost > 0 || $i > 0) {
                $this->review($procedure, $teacher, $teacherUser, $this->chance(90) ? 'autorizado' : 'correcciones');
            }
        }

        $realized = $plan->procedures()->where('status', 'realizado')->count();

        // Revisión del plan y de los diagnósticos.
        $decision = $realized > 1 ? 'autorizado' : $this->pick(['autorizado', 'autorizado', 'correcciones', null]);

        if ($decision) {
            $this->review($plan, $teacher, $teacherUser, $decision);
        }

        foreach ($plan->procedures()->whereNotNull('patient_diagnosis_id')->pluck('patient_diagnosis_id')->unique() as $diagnosisId) {
            $diagnosisDecision = $decision === 'autorizado' ? 'autorizado' : $this->pick(['autorizado', 'correcciones', null]);

            if ($diagnosisDecision) {
                $this->review(PatientDiagnosis::find($diagnosisId), $teacher, $teacherUser, $diagnosisDecision);
            }
        }

        if ($realized === $procedures->count()) {
            $plan->update(['status' => 'concluido', 'completed_at' => $plan->procedures()->max('performed_at')]);

            if ($this->chance(60)) {
                $patient->update(['status' => 'alta']);
            }

            return;
        }

        if ($this->chance(5)) {
            $plan->update(['status' => 'suspendido', 'notes' => 'Suspendido a solicitud del paciente por motivos económicos.']);

            return;
        }

        // Próxima cita para el siguiente procedimiento pendiente.
        $next = $plan->procedures()->with('procedure')->where('status', 'planeado')->first();
        $nextDate = $this->weekday(today()->addDays(mt_rand(0, 15)))->setTime(mt_rand(8, 17), 0);
        $this->appointment($patient, $clinic, $units, $student, $teacher, $plan, $nextDate, $this->chance(45) ? 'confirmada' : 'programada', $next?->procedure?->name ?? $next?->description);

        // Algunas inasistencias y cancelaciones en el historial.
        if ($this->chance(20) && $days > 14) {
            $this->appointment($patient, $clinic, $units, $student, $teacher, $plan, $this->weekday(today()->subDays(mt_rand(3, 12)))->setTime(mt_rand(8, 17), 0), 'no_asistio', 'Continuación del tratamiento');
        }

        if ($this->chance(12) && $days > 14) {
            $this->appointment($patient, $clinic, $units, $student, $teacher, $plan, $this->weekday(today()->subDays(mt_rand(2, 9)))->setTime(mt_rand(8, 17), 0), 'cancelada', 'Continuación del tratamiento');
        }
    }

    /** Odontograma después de realizar el procedimiento. */
    private function afterProcedure(Patient $patient, TreatmentProcedure $procedure, CarbonInterface $at, ?int $userId): void
    {
        if (! $procedure->tooth) {
            return;
        }

        $code = $procedure->procedure?->code;
        [$surface, $condition] = match (true) {
            in_array($code, ['OP-01', 'OP-02', 'OP-03'], true) => [$procedure->surfaces ?? 'O', 'obturado'],
            $code === 'PRV-03' => [$procedure->surfaces ?? 'O', 'sellador'],
            in_array($code, ['END-02', 'END-03'], true) => [null, 'endodoncia'],
            in_array($code, ['CIR-01', 'CIR-02'], true) => [null, 'ausente'],
            $code === 'PRO-01' => [null, 'corona'],
            $code === 'PRO-02' => [null, 'protesis'],
            str_contains((string) $procedure->description, 'implante') => [null, 'implante'],
            str_contains((string) $procedure->description, 'Corona') => [null, 'corona'],
            default => [null, null],
        };

        if ($condition) {
            $patient->odontogramEntries()->create([
                'patient_diagnosis_id' => $procedure->patient_diagnosis_id,
                'tooth' => $procedure->tooth,
                'surface' => $surface,
                'condition' => $condition,
                'recorded_at' => $at,
                'created_by' => $userId,
            ]);
        }
    }

    private function consumeMaterials(TreatmentProcedure $procedure, ?Warehouse $warehouse, ?int $userId): void
    {
        $materials = self::MATERIALS[$procedure->procedure?->code] ?? [];

        if (! $warehouse || ! $materials) {
            return;
        }

        Auth::setUser(User::find($userId));

        foreach ($materials as [$code, $quantity]) {
            $item = InventoryItem::firstWhere('code', $code);

            try {
                $this->inventory->withdraw($item, $warehouse, $quantity, [
                    'type' => 'consumo',
                    'reason' => 'Consumo en tratamiento',
                    'treatment_procedure_id' => $procedure->id,
                ]);
            } catch (ValidationException) {
                continue; // Sin existencia vigente: se registra sin descontar.
            }

            $procedure->materials()->create([
                'inventory_item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'name' => $item->name,
                'quantity' => $quantity,
                'unit' => $item->unit,
            ]);
        }
    }

    private function review(Model $reviewable, TeacherProfile $teacher, ?int $teacherUser, string $decision): void
    {
        Review::create([
            'reviewable_type' => $reviewable->getMorphClass(),
            'reviewable_id' => $reviewable->getKey(),
            'reviewer_id' => $teacherUser,
            'teacher_profile_id' => $teacher->id,
            'decision' => $decision,
            'comments' => match ($decision) {
                'autorizado' => $this->pick([null, 'Correcto.', 'Bien fundamentado.', 'Adelante.']),
                'correcciones' => $this->pick(['Falta radiografía de control.', 'Revisar el diagnóstico pulpar.', 'Completar la historia clínica.']),
                default => 'No procede.',
            },
        ]);
    }

    /**
     * Cita de una hora en una unidad libre. Si la hora está ocupada busca otra
     * hora del mismo día y luego días cercanos, sin cruzar de pasado a futuro.
     */
    private function appointment(Patient $patient, Clinic $clinic, Collection $units, StudentProfile $student, TeacherProfile $teacher, ?TreatmentPlan $plan, CarbonInterface $at, string $status, ?string $reason): ?CarbonInterface
    {
        $past = $at->lt(today());
        $hours = [8, 9, 10, 11, 12, 13, 15, 16, 17, 18];
        $startHour = in_array($at->hour, $hours, true) ? $at->hour : 9;
        $hours = [...array_filter($hours, fn ($h) => $h >= $startHour), ...array_filter($hours, fn ($h) => $h < $startHour)];

        foreach ([0, 1, -1, 2, -2, 3, -3, 4, 5, 6] as $offset) {
            $date = $at->copy()->startOfDay()->addWeekdays($offset);

            if ($date->isWeekend() || ($past && $date->gte(today())) || (! $past && $date->lt(today()))) {
                continue;
            }

            foreach ($hours as $hour) {
                if (! $past && $date->isToday() && $hour <= now()->hour) {
                    continue;
                }

                $key = $date->toDateString().'-'.$hour;

                if (isset($this->busy["s{$student->id}-{$key}"]) || isset($this->busy["p{$patient->id}-{$key}"])) {
                    continue;
                }

                $unit = $units->first(fn ($u) => ! isset($this->busy["u{$u->id}-{$key}"]) && $u->name !== 'Sala de rayos X');

                if (! $unit) {
                    continue;
                }

                $this->busy["u{$unit->id}-{$key}"] = $this->busy["s{$student->id}-{$key}"] = $this->busy["p{$patient->id}-{$key}"] = true;

                $start = $date->copy()->setTime($hour, 0);

                Appointment::create([
                    'patient_id' => $patient->id,
                    'clinic_id' => $clinic->id,
                    'space_id' => $unit->id,
                    'student_profile_id' => $student->id,
                    'teacher_profile_id' => $teacher->id,
                    'treatment_plan_id' => $plan?->id,
                    'date' => $date->toDateString(),
                    'start_time' => $this->time($hour),
                    'end_time' => $this->time($hour + 1),
                    'reason' => $reason,
                    'status' => $status,
                    'confirmed_at' => in_array($status, ['confirmada', 'atendida'], true) ? $start->copy()->subDay()->setTime(17, 0) : null,
                    'cancelled_at' => $status === 'cancelada' ? $start->copy()->subDays(1) : null,
                    'cancellation_reason' => $status === 'cancelada' ? $this->pick(['El paciente avisó que no podía asistir.', 'Enfermedad del paciente.', 'Unidad dental en mantenimiento.']) : null,
                    'reminder_sent_at' => $start->copy()->subDay()->lt(now()) ? $start->copy()->subDay()->setTime(10, 0) : null,
                    'notes' => $status === 'no_asistio' ? 'No se presentó ni avisó.' : null,
                    'created_by' => $this->receptionist?->id,
                ]);

                return $start;
            }
        }

        return null;
    }
}
