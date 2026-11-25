<?php

namespace Database\Seeders\Demo;

use App\Models\AcademicGroup;
use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\ClassSchedule;
use App\Models\Cohort;
use App\Models\Department;
use App\Models\KnowledgeArea;
use App\Models\Space;
use App\Models\Specialty;
use App\Models\StudentEnrollment;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

/**
 * Docentes, personal administrativo y alumnos (todos ficticios), con sus
 * generaciones, grupos del periodo vigente, materias asignadas y horarios.
 */
class FaodPeopleDemoSeeder extends Seeder
{
    use DemoHelpers;

    private const UJED = 'Universidad Juárez del Estado de Durango';

    /**
     * [género, título, áreas que imparte, especialidades, grados [nivel, título, institución?], contrato, áreas de conocimiento, coordina].
     * Contrato: ptc = tiempo completo, mt = medio tiempo, hora = por horas.
     */
    private const TEACHERS = [
        ['femenino', 'Cirujana Dentista', ['perio'], ['PERIO'], [['especialidad', 'Especialidad en Periodoncia'], ['maestria', 'Maestría en Ciencias Estomatológicas'], ['doctorado', 'Doctorado en Ciencias Biomédicas']], 'ptc', ['Investigación', 'Docencia'], 'EPI'],
        ['masculino', 'Cirujano Dentista', ['perio'], ['PERIO', 'REHAB'], [['especialidad', 'Especialidad en Periodoncia e Implantología']], 'ptc', ['Biomateriales'], null],
        ['masculino', 'Cirujano Dentista', ['orto'], ['ORTO'], [['especialidad', 'Especialidad en Ortodoncia', 'Universidad Autónoma de Nuevo León'], ['maestria', 'Maestría en Ciencias Estomatológicas']], 'ptc', ['Radiología e imagenología'], 'EORT'],
        ['femenino', 'Cirujana Dentista', ['orto'], ['ORTO'], [['especialidad', 'Especialidad en Ortodoncia', 'Universidad Autónoma de San Luis Potosí']], 'hora', [], null],
        ['femenino', 'Cirujana Dentista', ['odp'], ['ODPED'], [['especialidad', 'Especialidad en Odontopediatría', 'Universidad Autónoma de Nuevo León'], ['maestria', 'Maestría en Ciencias Estomatológicas']], 'ptc', ['Salud pública'], 'EODP'],
        ['femenino', 'Cirujana Dentista', ['odp'], ['ODPED'], [['especialidad', 'Especialidad en Odontopediatría', 'Universidad de Guadalajara']], 'hora', [], null],
        ['masculino', 'Cirujano Dentista', ['inv', 'pato'], [], [['maestria', 'Maestría en Ciencias Estomatológicas'], ['doctorado', 'Doctorado en Ciencias Biomédicas']], 'ptc', ['Investigación', 'Patología bucal'], 'MCE'],
        ['femenino', 'Cirujana Dentista', ['inv', 'salud'], [], [['maestria', 'Maestría en Salud Pública'], ['doctorado', 'Doctorado en Ciencias de la Salud Pública', 'Universidad de Guadalajara']], 'ptc', ['Salud pública', 'Investigación'], null],
        ['masculino', 'Cirujano Dentista', ['endo'], ['ENDO'], [['especialidad', 'Especialidad en Endodoncia', 'Universidad Autónoma de Nuevo León']], 'ptc', ['Farmacología'], null],
        ['femenino', 'Cirujana Dentista', ['endo'], ['ENDO'], [['especialidad', 'Especialidad en Endodoncia', 'Universidad Autónoma de Chihuahua']], 'hora', [], null],
        ['masculino', 'Cirujano Dentista', ['cir'], ['CIRMAX'], [['especialidad', 'Especialidad en Cirugía Oral y Maxilofacial', 'Universidad Nacional Autónoma de México']], 'mt', ['Anatomía y fisiología'], null],
        ['femenino', 'Cirujana Dentista', ['cir', 'clinica'], ['CIRMAX'], [['especialidad', 'Especialidad en Cirugía Bucal', 'Universidad Autónoma de Coahuila']], 'hora', [], null],
        ['femenino', 'Cirujana Dentista', ['proto'], ['PROTO'], [['especialidad', 'Especialidad en Prostodoncia', 'Universidad Autónoma de Nuevo León']], 'ptc', ['Biomateriales'], null],
        ['masculino', 'Cirujano Dentista', ['proto', 'oper'], ['PROTO', 'REHAB'], [['especialidad', 'Especialidad en Rehabilitación Oral', 'Universidad Autónoma de Sinaloa']], 'hora', ['Biomateriales'], null],
        ['femenino', 'Cirujana Dentista', ['oper', 'clinica'], ['OPER'], [['maestria', 'Maestría en Ciencias Estomatológicas']], 'ptc', ['Docencia'], 'LCD'],
        ['masculino', 'Cirujano Dentista', ['oper', 'clinica'], ['OPER'], [['especialidad', 'Especialidad en Odontología Restauradora', 'Universidad Autónoma de Chihuahua']], 'hora', [], null],
        ['femenino', 'Cirujana Dentista', ['clinica', 'salud'], ['GRAL'], [['maestria', 'Maestría en Salud Pública']], 'mt', ['Salud pública', 'Docencia'], null],
        ['masculino', 'Cirujano Dentista', ['clinica'], ['GRAL'], [], 'hora', [], null],
        ['femenino', 'Médica Cirujana', ['basicas'], [], [['maestria', 'Maestría en Ciencias Biomédicas']], 'ptc', ['Anatomía y fisiología', 'Docencia'], null],
        ['masculino', 'Químico Farmacéutico Biólogo', ['basicas'], [], [['maestria', 'Maestría en Ciencias Químicas']], 'hora', ['Farmacología'], null],
        ['femenino', 'Médica Cirujana', ['basicas'], [], [], 'hora', ['Anatomía y fisiología'], null],
        ['masculino', 'Cirujano Dentista', ['rx', 'pato'], ['GRAL'], [['especialidad', 'Especialidad en Radiología Oral y Maxilofacial', 'Universidad Nacional Autónoma de México']], 'hora', ['Radiología e imagenología'], null],
        ['femenino', 'Cirujana Dentista', ['pato', 'basicas'], [], [['maestria', 'Maestría en Ciencias Estomatológicas']], 'hora', ['Patología bucal'], null],
        ['masculino', 'Cirujano Dentista', ['salud', 'inv'], [], [['maestria', 'Maestría en Salud Pública']], 'mt', ['Salud pública'], null],
        ['femenino', 'Cirujana Dentista', ['perio', 'clinica'], ['PERIO'], [['especialidad', 'Especialidad en Periodoncia e Implantología']], 'hora', [], null],
        ['masculino', 'Cirujano Dentista', ['orto', 'clinica'], ['ORTO'], [['especialidad', 'Especialidad en Ortodoncia', 'Universidad Autónoma de Nuevo León']], 'hora', [], null],
    ];

    /** [puesto, departamento, género]. */
    private const STAFF = [
        ['Asistente de Dirección', 'DIR', 'femenino'],
        ['Jefa de Servicios Escolares', 'ESC', 'femenino'],
        ['Auxiliar de Servicios Escolares', 'ESC', 'masculino'],
        ['Cajera', 'CAJA', 'femenino'],
        ['Cajero', 'CAJA', 'masculino'],
        ['Encargado de almacén', 'ALM', 'masculino'],
        ['Auxiliar de almacén', 'ALM', 'femenino'],
        ['Recepcionista de clínicas', 'CLIN', 'femenino'],
        ['Asistente dental', 'CLIN', 'femenino'],
        ['Auxiliar de CEyE (esterilización)', 'CLIN', 'masculino'],
        ['Bibliotecaria', 'BIB', 'femenino'],
        ['Auxiliar de Educación Continua', 'EDUC', 'femenino'],
        ['Psicóloga', 'PSI', 'femenino'],
    ];

    /** Grupos del periodo vigente por programa: [alumnos por grupo, máximo de generaciones, grupos de primer ingreso]. */
    private const INTAKE = [
        'LCD' => [10, 5, 2],
        'EPI' => [4, 3, 1],
        'EORT' => [4, 2, 1],
        'EODP' => [4, 2, 1],
        'MCE' => [3, 2, 1],
    ];

    private AcademicPeriod $period;

    private int $sequence = 0;

    /** @var array<int, array<string, true>> ocupación "día-hora" por docente, grupo y espacio */
    private array $teacherBusy = [];

    private array $groupBusy = [];

    private array $spaceBusy = [];

    /** @var array<int, int> bloques asignados por docente */
    private array $load = [];

    /** @var array<int, array{tags: array<int, string>, contract: string}> */
    private array $teacherMeta = [];

    public function run(): void
    {
        $this->period = AcademicPeriod::current() ?? throw new \RuntimeException('No hay periodo vigente.');

        $this->teachers();
        $this->staff();
        $this->students();
        $this->teacherAvailability();
    }

    private function teachers(): void
    {
        foreach (self::TEACHERS as $i => [$gender, $title, $tags, $specialties, $degrees, $contract, $areas, $coordinates]) {
            $person = $this->makePerson($gender, [], 32, 64);
            $this->makeUser($person, 'docentes.faod.test', 'docente');

            $graduation = Date::parse($person->birth_date)->addYears(23);
            $experience = max(3, (int) $graduation->diffInYears(today()));
            $doctor = $gender === 'femenino' ? 'Dra.' : 'Dr.';
            $prefix = match (true) {
                collect($degrees)->contains(fn ($d) => $d[0] === 'doctorado') => $doctor,
                str_contains($title, 'Dentista') => 'C.D.',
                str_contains($title, 'Médic') => $doctor,
                default => 'Q.F.B.',
            };

            $teacher = TeacherProfile::create([
                'people_id' => $person->id,
                'employee_number' => sprintf('D%05d', 10230 + $i * 37),
                'professional_license' => (string) (4100000 + $i * 31337),
                'professional_title' => $title,
                'years_experience' => $experience,
                'status' => 'active',
                'professional_biography' => sprintf(
                    '%s %s. %s por la %s. %s Docente de la Facultad de Odontología desde %d%s.',
                    $prefix,
                    $person->full_name,
                    $title,
                    self::UJED,
                    $degrees ? 'Cuenta con '.collect($degrees)->map(fn ($d) => mb_strtolower($d[1]))->join(', ', ' y ').'.' : '',
                    (int) today()->subYears(min($experience, mt_rand(3, 20)))->format('Y'),
                    $contract === 'ptc' ? ' como profesor de tiempo completo' : '',
                ),
            ]);

            $teacher->degrees()->create([
                'level' => 'licenciatura',
                'title' => $title,
                'institution' => str_contains($title, 'Dentista') ? self::UJED : $this->pick([self::UJED, 'Universidad Autónoma de Chihuahua']),
                'license_number' => $teacher->professional_license,
                'obtained_at' => $graduation->toDateString(),
            ]);

            foreach ($degrees as $n => $degree) {
                $teacher->degrees()->create([
                    'level' => $degree[0],
                    'title' => $degree[1],
                    'institution' => $degree[2] ?? self::UJED,
                    'license_number' => (string) mt_rand(5000000, 13999999),
                    'obtained_at' => $graduation->copy()->addYears(3 + $n * 3)->min(today()->subYear())->toDateString(),
                ]);
            }

            if ($specialties) {
                $teacher->specialties()->sync(Specialty::whereIn('code', $specialties)->pluck('id'));
            }

            if ($areas) {
                $teacher->knowledgeAreas()->sync(KnowledgeArea::whereIn('name', $areas)->pluck('id'));
            }

            if ($this->chance(60)) {
                $teacher->certifications()->create([
                    'name' => $specialties ? 'Certificación vigente del Consejo de la especialidad' : 'Certificación en docencia universitaria',
                    'issuer' => $specialties ? 'Consejo Mexicano de la especialidad' : self::UJED,
                    'folio' => 'CERT-'.mt_rand(10000, 99999),
                    'issued_at' => today()->subMonths(mt_rand(6, 40))->toDateString(),
                    'expires_at' => today()->addMonths(mt_rand(-2, 40))->toDateString(),
                ]);
            }

            $teacher->experiences()->create([
                'institution' => 'Consultorio dental particular',
                'position' => $specialties ? 'Práctica privada de la especialidad' : 'Práctica privada',
                'started_at' => $graduation->copy()->addYear()->toDateString(),
                'description' => 'Atención de pacientes de práctica general y de especialidad.',
            ]);

            if ($this->chance(40)) {
                $teacher->experiences()->create([
                    'institution' => $this->pick(['Instituto Mexicano del Seguro Social', 'ISSSTE', 'Secretaría de Salud del Estado de Durango']),
                    'position' => 'Cirujano dentista adscrito',
                    'started_at' => $graduation->copy()->addYears(2)->toDateString(),
                    'ended_at' => $graduation->copy()->addYears(6)->min(today()->subYear())->toDateString(),
                ]);
            }

            if ($coordinates) {
                $program = AcademicProgram::firstWhere('code', $coordinates);
                $program?->teachers()->attach($teacher->id, ['role' => 'coordinador', 'starts_at' => today()->subYears(2)->startOfYear()->toDateString()]);
            }

            $this->teacherMeta[$teacher->id] = ['tags' => $tags, 'contract' => $contract];
            $this->load[$teacher->id] = 0;
        }
    }

    private function staff(): void
    {
        foreach (self::STAFF as $i => [$position, $dept, $gender]) {
            $person = $this->makePerson($gender, [], 24, 58);
            $this->makeUser($person, 'faod.test', 'personal_administrativo');

            $person->staffProfile()->create([
                'employee_number' => sprintf('A%05d', 20110 + $i * 23),
                'position' => $position,
                'department_id' => Department::firstWhere('code', $dept)?->id,
                'status' => 'active',
                'hired_at' => today()->subYears(mt_rand(1, 18))->subDays(mt_rand(0, 300))->toDateString(),
            ]);
        }
    }

    private function students(): void
    {
        $year = (int) $this->period->starts_at->format('Y');
        $isB = str_ends_with($this->period->code, 'B');
        $verifier = User::whereHas('people.staffProfile', fn ($q) => $q->where('position', 'like', '%Servicios Escolares%'))->first();
        $aulas = Space::where('code', 'like', 'AUL-A%')->orderBy('code')->pluck('id')->all();
        $postgradAulas = Space::where('code', 'like', 'AUL-P%')->orderBy('code')->pluck('id')->all();
        $aulaIndex = 0;

        foreach (self::INTAKE as $code => [$perGroup, $maxCohorts, $firstGroups]) {
            $program = AcademicProgram::with('studyPlans')->firstWhere('code', $code);
            $plan = $program->studyPlans->first();
            $created = 0;

            // De la generación más nueva a la más antigua.
            for ($entry = $year; $created < $maxCohorts; $entry--) {
                $semester = ($year - $entry) * 2 + ($isB ? 1 : 0);

                if ($semester < 1) {
                    continue;
                }

                if ($semester > $program->total_periods) {
                    break;
                }

                $years = (int) ceil($program->total_periods / 2);
                $cohort = Cohort::create([
                    'academic_program_id' => $program->id,
                    'name' => $entry.'-'.($entry + $years),
                    'starts_at' => "{$entry}-08-01",
                    'ends_at' => ($entry + $years).'-06-30',
                    'status' => 'en_curso',
                ]);

                $groups = $semester === 1 ? $firstGroups : 1;

                for ($g = 0; $g < $groups; $g++) {
                    $suffix = chr(65 + $g);
                    $group = AcademicGroup::create([
                        'cohort_id' => $cohort->id,
                        'academic_period_id' => $this->period->id,
                        'period_number' => $semester,
                        'code' => $code === 'LCD' ? "LCD-{$semester}{$suffix}" : "{$code}-{$semester}",
                        'capacity' => $code === 'LCD' ? 40 : 8,
                    ]);

                    $homeAula = $code === 'LCD' ? $aulas[$aulaIndex++ % count($aulas)] : $postgradAulas[$created % count($postgradAulas)];

                    for ($s = 0; $s < $perGroup; $s++) {
                        $student = $this->student($program, $cohort, $plan->id, $entry, $verifier, $semester);
                        $group->students()->attach($student->id);
                    }

                    $this->scheduleGroup($group, $program, $plan->id, $semester, $homeAula);
                }

                $created++;
            }

            // Una generación que ya egresó (solo licenciatura).
            if ($code === 'LCD') {
                $entry = $year - 5 - ($isB ? 0 : 1);
                $cohort = Cohort::create([
                    'academic_program_id' => $program->id,
                    'name' => $entry.'-'.($entry + 5),
                    'starts_at' => "{$entry}-08-01",
                    'ends_at' => ($entry + 5).'-06-30',
                    'status' => 'concluida',
                ]);

                for ($s = 0; $s < 6; $s++) {
                    $this->student($program, $cohort, $plan->id, $entry, $verifier, null, graduated: true);
                }
            }
        }
    }

    private function student(AcademicProgram $program, Cohort $cohort, int $planId, int $entry, ?User $verifier, ?int $semester, bool $graduated = false): StudentProfile
    {
        $age = $program->type === 'licenciatura' ? 18 + ($semester ? intdiv($semester, 2) : 6) : mt_rand(25, 33);
        $person = $this->makePerson(null, [], $age, $age + 2);
        $this->makeUser($person, 'alumnos.faod.test', 'alumno');

        // Bajas: una de cada ~25 inscripciones de licenciatura.
        $withdrawn = ! $graduated && $program->code === 'LCD' && $semester > 1 && $this->chance(4);

        $student = StudentProfile::create([
            'people_id' => $person->id,
            'registration_number' => sprintf('%d%05d', $entry, 10000 + ++$this->sequence * 7),
            'status' => $graduated ? 'graduated' : ($withdrawn ? 'inactive' : 'active'),
            'admission_date' => $cohort->starts_at->toDateString(),
        ]);

        $enrollment = StudentEnrollment::create([
            'student_profile_id' => $student->id,
            'academic_program_id' => $program->id,
            'cohort_id' => $cohort->id,
            'study_plan_id' => $planId,
            'status' => $graduated ? 'egresado' : ($withdrawn ? 'baja' : 'activo'),
            'started_at' => $cohort->starts_at->toDateString(),
            'graduated_at' => $graduated ? $cohort->ends_at->toDateString() : null,
            'withdrawn_at' => $withdrawn ? today()->subMonths(2)->toDateString() : null,
            'withdrawal_reason' => $withdrawn ? 'Baja temporal por motivos económicos.' : null,
        ]);

        if ($graduated || $withdrawn) {
            $enrollment->statusChanges()->create([
                'from_status' => 'activo',
                'to_status' => $enrollment->status,
                'reason' => $graduated ? 'Concluyó el plan de estudios.' : $enrollment->withdrawal_reason,
                'changed_at' => $enrollment->graduated_at ?? $enrollment->withdrawn_at,
                'user_id' => $verifier?->id,
            ]);
        }

        if (! $graduated && ! $withdrawn) {
            $status = $this->chance(85) ? 'inscrito' : ($this->chance(70) ? 'pendiente' : 'no_inscrito');

            $enrollment->universityRegistrations()->create([
                'academic_period_id' => $this->period->id,
                'status' => $status,
                'registered_at' => $status === 'inscrito' ? $this->period->starts_at->copy()->addDays(mt_rand(0, 20))->toDateString() : null,
                'verified_by' => $status === 'inscrito' ? $verifier?->id : null,
                'notes' => $status === 'no_inscrito' ? 'No presentó comprobante de pago de inscripción.' : null,
            ]);
        }

        return $student;
    }

    /**
     * Asigna docente a cada materia del semestre y le busca horario sin choques de
     * grupo, docente ni espacio: aula o laboratorio en bloques de 2 h y clínica en
     * bloques de 4 h (7 a 11 u 11 a 15).
     */
    private function scheduleGroup(AcademicGroup $group, AcademicProgram $program, int $planId, int $semester, int $homeAula): void
    {
        $subjects = FaodAcademicDemoSeeder::PLANS[$program->code][$semester] ?? [];
        $clinicHall = match ($program->code) {
            'EPI' => ['CLIN-PERIO-SA'],
            'EORT' => ['CLIN-ORTO-SA'],
            'EODP' => ['CLIN-ODP-SA'],
            default => ['CLIN-LIC-SA', 'CLIN-LIC-SB'],
        };
        $clinicHallIds = Space::whereIn('code', $clinicHall)->pluck('id')->all();

        foreach ($subjects as $index => [$name, , , , $tag, $kind]) {
            $subject = Subject::firstWhere('code', FaodAcademicDemoSeeder::subjectCode($program->code, $semester, $index));
            $teacherId = $this->teacherFor($tag, $program->type !== 'licenciatura');

            $section = $group->classSections()->create([
                'subject_id' => $subject->id,
                'teacher_profile_id' => $teacherId,
            ]);

            $program->teachers()->syncWithoutDetaching([$teacherId => ['role' => 'docente']]);

            $spaces = match ($kind) {
                'c' => $clinicHallIds,
                'l' => [Space::firstWhere('code', match ($tag) {
                    'basicas', 'pato' => 'LAB-CBA',
                    'proto' => 'LAB-PRO',
                    'inv' => 'LAB-INV',
                    default => 'LAB-SIM',
                })->id],
                default => [$homeAula],
            };

            $meetings = $kind === 'c' ? ($program->type === 'licenciatura' ? 2 : 3) : 2;
            $length = $kind === 'c' ? 2 : 1;
            $usedDays = [];

            for ($m = 0; $m < $meetings; $m++) {
                $slot = $this->findSlot($group->id, $teacherId, $spaces, $length, $usedDays, $group->id + $index);

                if (! $slot) {
                    continue;
                }

                [$day, $block, $spaceId] = $slot;
                $usedDays[] = $day;

                ClassSchedule::create([
                    'class_section_id' => $section->id,
                    'space_id' => $spaceId,
                    'day_of_week' => $day,
                    'start_time' => $this->time(7 + $block * 2),
                    'end_time' => $this->time(7 + ($block + $length) * 2),
                ]);

                for ($b = $block; $b < $block + $length; $b++) {
                    $this->teacherBusy[$teacherId]["{$day}-{$b}"] = true;
                    $this->groupBusy[$group->id]["{$day}-{$b}"] = true;
                    $this->spaceBusy[$spaceId]["{$day}-{$b}"] = true;
                }

                $this->load[$teacherId] += $length;
            }
        }
    }

    /** @return array{0: int, 1: int, 2: int}|null [día, bloque, espacio] */
    private function findSlot(int $groupId, int $teacherId, array $spaces, int $length, array $usedDays, int $rotation): ?array
    {
        $days = [1, 2, 3, 4, 5];
        $days = [...array_slice($days, $rotation % 5), ...array_slice($days, 0, $rotation % 5)];
        $blocks = $length === 2 ? [0, 2] : [0, 1, 2, 3];

        // Primero días distintos a los ya usados por la materia.
        foreach ([true, false] as $distinctDay) {
            foreach ($days as $day) {
                if ($distinctDay && in_array($day, $usedDays, true)) {
                    continue;
                }

                foreach ($blocks as $block) {
                    foreach ($spaces as $spaceId) {
                        $free = true;

                        for ($b = $block; $b < $block + $length; $b++) {
                            $key = "{$day}-{$b}";

                            if (isset($this->teacherBusy[$teacherId][$key]) || isset($this->groupBusy[$groupId][$key]) || isset($this->spaceBusy[$spaceId][$key])) {
                                $free = false;
                                break;
                            }
                        }

                        if ($free) {
                            return [$day, $block, $spaceId];
                        }
                    }
                }
            }
        }

        return null;
    }

    /** El docente del área con menos carga; en posgrado se prefiere a los de tiempo completo. */
    private function teacherFor(string $tag, bool $postgrad): int
    {
        $candidates = collect($this->teacherMeta)->filter(fn ($meta) => in_array($tag, $meta['tags'], true));

        if ($candidates->isEmpty()) {
            $candidates = collect($this->teacherMeta)->filter(fn ($meta) => in_array('clinica', $meta['tags'], true));
        }

        return $candidates
            ->keys()
            ->sortBy(fn ($id) => $this->load[$id] - ($postgrad && $this->teacherMeta[$id]['contract'] === 'ptc' ? 3 : 0))
            ->first();
    }

    /**
     * Disponibilidad del periodo: tiempo completo de lunes a viernes; medio
     * tiempo y por horas, los días que tienen clase y uno más.
     */
    private function teacherAvailability(): void
    {
        foreach ($this->teacherMeta as $teacherId => $meta) {
            $days = collect(array_keys($this->teacherBusy[$teacherId] ?? []))
                ->map(fn ($key) => (int) explode('-', $key)[0])
                ->unique();

            if ($meta['contract'] === 'ptc') {
                $days = collect([1, 2, 3, 4, 5]);
            } else {
                $days->push($this->pick(array_values(array_diff([1, 2, 3, 4, 5], $days->all())) ?: [1]));
            }

            $teacher = TeacherProfile::find($teacherId);

            foreach ($days->unique()->sort() as $day) {
                $teacher->availabilities()->create([
                    'academic_period_id' => $this->period->id,
                    'day_of_week' => $day,
                    'start_time' => $this->time(7),
                    'end_time' => $this->time(15),
                ]);

                // Tiempo completo: también tarde de lunes a jueves (asesorías e investigación).
                if ($meta['contract'] === 'ptc' && $day <= 4) {
                    $teacher->availabilities()->create([
                        'academic_period_id' => $this->period->id,
                        'day_of_week' => $day,
                        'start_time' => $this->time(16),
                        'end_time' => $this->time(19),
                    ]);
                }
            }
        }
    }
}
