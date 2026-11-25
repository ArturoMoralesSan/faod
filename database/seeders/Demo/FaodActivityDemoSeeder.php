<?php

namespace Database\Seeders\Demo;

use App\Models\AcademicActivity;
use App\Models\FeeConcept;
use App\Models\People;
use App\Models\Space;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\FinanceService;
use Illuminate\Database\Seeder;

/**
 * Educación continua y extensión: congreso, cursos, taller, diplomado, brigada
 * comunitaria, seminario y conferencia, con ponentes, participantes,
 * constancias y pagos de inscripción.
 */
class FaodActivityDemoSeeder extends Seeder
{
    use DemoHelpers;

    private int $folio = 0;

    public function run(FinanceService $finance): void
    {
        $cashier = User::whereHas('people.staffProfile', fn ($q) => $q->where('position', 'Cajera'))->first();
        $creator = User::whereHas('people.staffProfile', fn ($q) => $q->where('position', 'Auxiliar de Educación Continua'))->first();
        $concept = FeeConcept::firstWhere('code', 'CURSO');
        $space = fn (string $code) => Space::firstWhere('code', $code)?->id;
        $teacher = fn (string $title) => TeacherProfile::whereHas('degrees', fn ($q) => $q->where('title', 'like', "%{$title}%"))->first();

        // Odontólogos externos (egresados de práctica privada) que toman cursos.
        $externals = collect(range(1, 12))->map(fn () => $this->makePerson(null, [], 26, 55));

        $activities = [
            [
                'congreso', 'Jornadas Odontológicas FAOD '.today()->addDays(45)->format('Y'), 45, 3, 'Auditorio de la Facultad', 'AUD-01', 24, 1200, 300,
                'Conferencias magistrales, presentación de casos clínicos de alumnos y residentes, concurso de carteles y exposición comercial.',
                'Actualizar a la comunidad odontológica del estado en los avances de las distintas especialidades.',
                'Periodoncia', [['masculino', 'conferencista magistral'], ['femenino', 'conferencista magistral'], ['masculino', 'ponente']], 'abierta', 30,
            ],
            [
                'curso', 'Curso de fotografía clínica dental', 18, 2, 'Sala de cómputo', 'CMP-01', 16, 1500, 20,
                'Técnicas de fotografía intraoral y extraoral con cámara réflex y teléfono celular para documentar casos clínicos.',
                'Que el participante documente sus casos con fotografías de calidad para expediente y publicación.',
                'Ortodoncia', [], 'abierta', 9,
            ],
            [
                'taller', 'Taller de cirugía de terceros molares en modelos', 30, 1, 'Laboratorio de simulación clínica', 'LAB-SIM', 8, 900, 15,
                'Práctica de colgajos, osteotomía, odontosección y sutura en modelos de simulación.',
                'Desarrollar destrezas quirúrgicas básicas para la extracción de terceros molares.',
                'Cirugía Oral', [], 'abierta', 6,
            ],
            [
                'diplomado', 'Diplomado en Endodoncia Clínica', -60, 180, 'Clínica de Licenciatura', 'LAB-SIM', 160, 15000, 12,
                'Diplomado de seis meses con módulos teóricos y práctica clínica supervisada en tratamientos de conductos.',
                'Que el odontólogo de práctica general resuelva con seguridad tratamientos de conductos uni y multirradiculares.',
                'Endodoncia', [], 'en_curso', 10,
            ],
            [
                'jornada', 'Brigada de salud bucal en comunidad rural', -25, 1, 'Municipio de Durango', null, 8, null, null,
                'Revisión, profilaxis, aplicación de flúor y pláticas de higiene para niñas, niños y adultos de la comunidad, con alumnos de servicio social.',
                'Llevar atención preventiva a comunidades con poco acceso a servicios dentales.',
                'Salud Pública', [], 'concluida', 14,
            ],
            [
                'seminario', 'Seminario de avances de tesis de la Maestría en Ciencias Estomatológicas', -40, 2, 'Auditorio de la Facultad', 'AUD-01', 12, null, 60,
                'Presentación de avances de los proyectos de tesis ante el núcleo académico y estudiantes de posgrado.',
                'Retroalimentar los proyectos de investigación de los estudiantes de la maestría.',
                'Ciencias Biomédicas', [], 'concluida', 12,
            ],
            [
                'conferencia', 'Conferencia: manejo del paciente con diabetes en la consulta dental', -10, 1, 'Auditorio de la Facultad', 'AUD-01', 2, null, 200,
                'Consideraciones médicas, interacciones farmacológicas y manejo de urgencias en el paciente diabético.',
                'Reconocer los riesgos y ajustar el tratamiento dental del paciente con diabetes.',
                'Salud Pública', [], 'concluida', 25,
            ],
            [
                'curso', 'Curso de reanimación cardiopulmonar básica para personal de clínicas', 70, 1, 'Auditorio de la Facultad', 'AUD-01', 8, 600, 40,
                'Curso teórico-práctico de soporte vital básico con maniquíes de entrenamiento.',
                'Que el personal y los alumnos de clínica respondan ante una urgencia médica.',
                'Cirugía Oral', [], 'planeada', 0,
            ],
        ];

        foreach ($activities as [$type, $title, $offset, $days, $location, $spaceCode, $hours, $cost, $capacity, $description, $objectives, $coordinatorArea, $guests, $status, $participants]) {
            $coordinator = $teacher($coordinatorArea) ?? TeacherProfile::first();
            $starts = today()->addDays($offset);
            $concluded = $status === 'concluida';

            $activity = AcademicActivity::create([
                'type' => $type,
                'title' => $title,
                'description' => $description,
                'objectives' => $objectives,
                'modality' => 'presencial',
                'starts_at' => $starts->toDateString(),
                'ends_at' => $starts->copy()->addDays($days - 1)->toDateString(),
                'schedule' => $type === 'diplomado' ? 'Viernes de 16:00 a 21:00 y sábados de 9:00 a 14:00' : '9:00 a 14:00',
                'space_id' => $spaceCode ? $space($spaceCode) : null,
                'location' => $location,
                'capacity' => $capacity,
                'hours' => $hours,
                'cost' => $cost,
                'status' => $status,
                'coordinator_id' => $coordinator->id,
                'issues_certificate' => $type !== 'jornada',
                'min_attendance' => 80,
                'evaluation_score' => $concluded ? mt_rand(86, 98) / 10 : null,
                'evaluation_notes' => $concluded ? 'Los participantes valoraron bien los contenidos; piden más tiempo de práctica.' : null,
                'results' => match (true) {
                    $type === 'jornada' && $concluded => 'Se atendieron '.mt_rand(120, 220).' personas: revisiones, profilaxis y aplicación de flúor.',
                    $concluded => 'Actividad realizada conforme a lo programado.',
                    default => null,
                },
                'created_by' => $creator?->id,
            ]);

            $activity->instructors()->create(['teacher_profile_id' => $coordinator->id, 'role' => 'coordinador']);

            // Ponentes invitados (ficticios).
            foreach ($guests as [$gender, $role]) {
                $activity->instructors()->create([
                    'name' => ($gender === 'femenino' ? 'Dra. ' : 'Dr. ').$this->fullName($gender),
                    'role' => $role,
                ]);
            }

            if (in_array($type, ['curso', 'taller', 'diplomado', 'seminario'], true)) {
                $other = TeacherProfile::whereKeyNot($coordinator->id)->inRandomOrder()->first();
                $activity->instructors()->create(['teacher_profile_id' => $other->id, 'role' => 'ponente']);
            }

            $this->participants($activity, $participants, $externals, $finance, $cashier, $concept);
        }
    }

    private function participants(AcademicActivity $activity, int $count, $externals, FinanceService $finance, ?User $cashier, ?FeeConcept $concept): void
    {
        if ($count === 0) {
            return;
        }

        // Quién asiste: residentes y externos a los cursos; alumnos a jornadas y conferencias.
        $students = StudentProfile::where('status', 'active')->with('people')->inRandomOrder()->limit($count)->get()->pluck('people');
        $pool = match ($activity->type) {
            'diplomado', 'curso', 'taller' => $externals->concat($students),
            'seminario' => StudentProfile::whereHas('enrollments.academicProgram', fn ($q) => $q->where('code', 'MCE'))->with('people')->get()->pluck('people')
                ->concat(TeacherProfile::with('people')->inRandomOrder()->limit(6)->get()->pluck('people')),
            default => $students->concat($externals->take(4)),
        };

        $concluded = $activity->status === 'concluida';
        $paid = $activity->cost > 0;

        foreach ($pool->unique('id')->take($count) as $person) {
            /** @var People $person */
            $attendance = $concluded ? $this->pick([100, 100, 95, 90, 85, 70]) : ($activity->status === 'en_curso' ? $this->pick([100, 90, 80]) : null);
            $accredited = $concluded && $attendance >= $activity->min_attendance;

            $participant = $activity->participants()->create([
                'people_id' => $person->id,
                'status' => $concluded ? ($accredited ? 'acreditado' : 'no_acreditado') : 'inscrito',
                'attendance' => $attendance,
                'grade' => $concluded && $activity->type !== 'conferencia' ? mt_rand(70, 100) / 10 : null,
                'payment_status' => $paid ? 'pendiente' : 'no_aplica',
                'certificate_folio' => $accredited && $activity->issues_certificate ? sprintf('FAOD-EC-%s-%04d', today()->format('Y'), ++$this->folio) : null,
                'certificate_issued_at' => $accredited && $activity->issues_certificate ? $activity->ends_at->copy()->addDays(3)->setTime(10, 0) : null,
            ]);

            // La mayoría ya pagó su inscripción (con tarjeta o transferencia).
            if ($paid && $cashier && $this->chance(70)) {
                $finance->registerIncome([
                    'amount' => $activity->cost,
                    'method' => $this->pick(['transferencia', 'tarjeta']),
                    'activity_participant_id' => $participant->id,
                    'fee_concept_id' => $concept?->id,
                    'reference' => 'EC-'.mt_rand(100000, 999999),
                    'paid_at' => min(now(), $activity->starts_at->copy()->subDays(mt_rand(1, 10))->setTime(11, 0)),
                ], $cashier);
            }
        }
    }
}
