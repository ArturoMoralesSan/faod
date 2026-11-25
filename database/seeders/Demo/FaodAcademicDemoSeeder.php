<?php

namespace Database\Seeders\Demo;

use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\Department;
use App\Models\Space;
use App\Models\Specialty;
use App\Models\StudyPlan;
use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * Oferta académica de la FAOD (UJED): Licenciatura de Cirujano Dentista,
 * Maestría en Ciencias Estomatológicas y las especialidades en Periodoncia e
 * Implantología, Ortodoncia y Odontopediatría. Los programas son los reales;
 * los planes de estudio, cargas y generaciones son de demostración.
 */
class FaodAcademicDemoSeeder extends Seeder
{
    /**
     * Materias por programa y periodo: [nombre, créditos, horas teoría, horas práctica, área, tipo].
     * Área: para elegir al docente. Tipo: t = aula, l = laboratorio, c = clínica.
     */
    public const PLANS = [
        'LCD' => [
            1 => [
                ['Anatomía humana', 8, 64, 32, 'basicas', 't'],
                ['Histología y embriología general', 6, 48, 16, 'basicas', 'l'],
                ['Bioquímica', 6, 48, 16, 'basicas', 't'],
                ['Introducción a la odontología', 4, 32, 0, 'salud', 't'],
                ['Metodología de la investigación', 4, 32, 0, 'inv', 't'],
            ],
            2 => [
                ['Anatomía de cabeza y cuello', 8, 64, 32, 'basicas', 't'],
                ['Fisiología', 6, 48, 16, 'basicas', 't'],
                ['Histología y embriología bucodental', 6, 48, 16, 'basicas', 'l'],
                ['Anatomía dental', 6, 32, 48, 'oper', 'l'],
                ['Bioética', 4, 32, 0, 'salud', 't'],
            ],
            3 => [
                ['Microbiología e inmunología', 6, 48, 16, 'basicas', 'l'],
                ['Patología general', 6, 48, 16, 'pato', 't'],
                ['Farmacología', 6, 48, 0, 'basicas', 't'],
                ['Materiales dentales', 6, 32, 48, 'oper', 'l'],
                ['Propedéutica clínica', 6, 32, 48, 'clinica', 't'],
            ],
            4 => [
                ['Patología bucal', 6, 48, 16, 'pato', 't'],
                ['Radiología bucodental', 6, 32, 48, 'rx', 'l'],
                ['Operatoria dental I', 8, 32, 64, 'oper', 'l'],
                ['Prevención y salud pública bucal', 6, 48, 16, 'salud', 't'],
                ['Anestesiología dental', 4, 32, 16, 'cir', 't'],
            ],
            5 => [
                ['Operatoria dental II', 8, 32, 64, 'oper', 'l'],
                ['Periodoncia I', 6, 32, 32, 'perio', 't'],
                ['Endodoncia I', 6, 32, 48, 'endo', 'l'],
                ['Prótesis fija I', 6, 32, 48, 'proto', 'l'],
                ['Clínica integral I', 10, 0, 160, 'clinica', 'c'],
            ],
            6 => [
                ['Periodoncia II', 6, 32, 32, 'perio', 't'],
                ['Endodoncia II', 6, 32, 48, 'endo', 'l'],
                ['Prótesis parcial removible', 6, 32, 48, 'proto', 'l'],
                ['Cirugía bucal I', 6, 32, 48, 'cir', 't'],
                ['Clínica integral II', 10, 0, 160, 'clinica', 'c'],
            ],
            7 => [
                ['Odontopediatría I', 6, 32, 32, 'odp', 't'],
                ['Ortodoncia I', 6, 32, 32, 'orto', 't'],
                ['Prótesis total', 6, 32, 48, 'proto', 'l'],
                ['Cirugía bucal II', 6, 32, 48, 'cir', 't'],
                ['Clínica integral III', 10, 0, 160, 'clinica', 'c'],
            ],
            8 => [
                ['Odontopediatría II', 6, 32, 32, 'odp', 't'],
                ['Ortodoncia II', 6, 32, 32, 'orto', 't'],
                ['Oclusión', 6, 32, 32, 'proto', 't'],
                ['Odontología legal y forense', 4, 32, 0, 'salud', 't'],
                ['Clínica integral IV', 10, 0, 160, 'clinica', 'c'],
            ],
            9 => [
                ['Clínica integral del adulto', 12, 0, 192, 'clinica', 'c'],
                ['Clínica del niño y del adolescente', 8, 0, 128, 'odp', 'c'],
                ['Administración del consultorio dental', 4, 32, 0, 'salud', 't'],
                ['Seminario de titulación I', 4, 32, 0, 'inv', 't'],
            ],
            10 => [
                ['Clínica integral avanzada', 12, 0, 192, 'clinica', 'c'],
                ['Odontología comunitaria', 6, 16, 80, 'salud', 't'],
                ['Urgencias médico-dentales', 4, 32, 16, 'cir', 't'],
                ['Seminario de titulación II', 4, 32, 0, 'inv', 't'],
            ],
        ],
        'MCE' => [
            1 => [
                ['Metodología de la investigación científica', 8, 64, 0, 'inv', 't'],
                ['Bioestadística', 6, 48, 16, 'inv', 't'],
                ['Biología molecular bucal', 6, 48, 16, 'inv', 'l'],
            ],
            2 => [
                ['Seminario de tesis I', 8, 32, 64, 'inv', 't'],
                ['Epidemiología bucal', 6, 48, 0, 'salud', 't'],
                ['Biomateriales avanzados', 6, 32, 32, 'oper', 'l'],
            ],
            3 => [
                ['Seminario de tesis II', 8, 32, 64, 'inv', 't'],
                ['Redacción de artículos científicos', 4, 32, 0, 'inv', 't'],
                ['Optativa: Patología molecular', 6, 48, 16, 'pato', 't'],
            ],
            4 => [
                ['Seminario de tesis III', 10, 32, 96, 'inv', 't'],
                ['Docencia en ciencias de la salud', 4, 32, 0, 'salud', 't'],
            ],
        ],
        'EPI' => [
            1 => [
                ['Biología del periodonto', 6, 48, 0, 'perio', 't'],
                ['Diagnóstico periodontal', 6, 32, 32, 'perio', 't'],
                ['Clínica de periodoncia I', 12, 0, 192, 'perio', 'c'],
            ],
            2 => [
                ['Terapia periodontal no quirúrgica', 6, 32, 32, 'perio', 't'],
                ['Farmacología aplicada a periodoncia', 4, 32, 0, 'perio', 't'],
                ['Clínica de periodoncia II', 12, 0, 192, 'perio', 'c'],
            ],
            3 => [
                ['Cirugía periodontal', 6, 32, 32, 'perio', 't'],
                ['Fundamentos de implantología', 6, 32, 32, 'perio', 't'],
                ['Clínica de periodoncia e implantología III', 12, 0, 192, 'perio', 'c'],
            ],
            4 => [
                ['Regeneración tisular guiada', 6, 32, 32, 'perio', 't'],
                ['Seminario de casos clínicos', 4, 32, 0, 'perio', 't'],
                ['Clínica de periodoncia e implantología IV', 12, 0, 192, 'perio', 'c'],
            ],
            5 => [
                ['Implantología avanzada', 6, 32, 32, 'perio', 't'],
                ['Seminario de investigación', 4, 32, 0, 'inv', 't'],
                ['Clínica de periodoncia e implantología V', 12, 0, 192, 'perio', 'c'],
            ],
            6 => [
                ['Rehabilitación sobre implantes', 6, 32, 32, 'proto', 't'],
                ['Clínica de periodoncia e implantología VI', 12, 0, 192, 'perio', 'c'],
            ],
        ],
        'EORT' => [
            1 => [
                ['Crecimiento y desarrollo craneofacial', 6, 48, 0, 'orto', 't'],
                ['Cefalometría', 6, 32, 32, 'orto', 't'],
                ['Clínica de ortodoncia I', 12, 0, 192, 'orto', 'c'],
            ],
            2 => [
                ['Biomecánica ortodóncica', 6, 32, 32, 'orto', 't'],
                ['Ortopedia dentofacial', 6, 32, 32, 'orto', 't'],
                ['Clínica de ortodoncia II', 12, 0, 192, 'orto', 'c'],
            ],
            3 => [
                ['Técnicas de arco recto', 6, 32, 32, 'orto', 't'],
                ['Seminario de investigación', 4, 32, 0, 'inv', 't'],
                ['Clínica de ortodoncia III', 12, 0, 192, 'orto', 'c'],
            ],
            4 => [
                ['Ortodoncia interdisciplinaria', 6, 32, 32, 'orto', 't'],
                ['Clínica de ortodoncia IV', 12, 0, 192, 'orto', 'c'],
            ],
            5 => [
                ['Ortodoncia quirúrgica', 6, 32, 32, 'orto', 't'],
                ['Clínica de ortodoncia V', 12, 0, 192, 'orto', 'c'],
            ],
            6 => [
                ['Retención y estabilidad', 4, 32, 0, 'orto', 't'],
                ['Clínica de ortodoncia VI', 12, 0, 192, 'orto', 'c'],
            ],
        ],
        'EODP' => [
            1 => [
                ['Crecimiento y desarrollo infantil', 6, 48, 0, 'odp', 't'],
                ['Psicología y manejo de conducta infantil', 6, 48, 0, 'odp', 't'],
                ['Clínica de odontopediatría I', 12, 0, 192, 'odp', 'c'],
            ],
            2 => [
                ['Terapia pulpar en dentición temporal', 6, 32, 32, 'odp', 't'],
                ['Ortodoncia preventiva e interceptiva', 6, 32, 32, 'orto', 't'],
                ['Clínica de odontopediatría II', 12, 0, 192, 'odp', 'c'],
            ],
            3 => [
                ['Paciente pediátrico con necesidades especiales', 6, 32, 32, 'odp', 't'],
                ['Seminario de investigación', 4, 32, 0, 'inv', 't'],
                ['Clínica de odontopediatría III', 12, 0, 192, 'odp', 'c'],
            ],
            4 => [
                ['Rehabilitación bucal pediátrica', 6, 32, 32, 'odp', 't'],
                ['Clínica de odontopediatría IV', 12, 0, 192, 'odp', 'c'],
            ],
        ],
    ];

    public function run(): void
    {
        $this->ensurePeriods();

        $department = fn (string $code) => Department::firstWhere('code', $code)?->id;
        $specialty = fn (?string $code) => $code ? Specialty::firstWhere('code', $code)?->id : null;

        $programs = [
            [
                'licenciatura', 'LCD', 'Licenciatura de Cirujano Dentista', 'SAC', null, 5, 'anios', 10,
                'Programa de licenciatura de la Facultad. Acreditado por el Consejo Nacional de Educación Odontológica (CONAEDO) desde 2009 y con nivel 1 de los CIEES. Desde los primeros semestres el alumno practica en las clínicas de la Facultad con pacientes de la comunidad, bajo supervisión docente.',
                [
                    ['objetivo', 'Formar cirujanos dentistas capaces de prevenir, diagnosticar y tratar las enfermedades bucales más frecuentes de la población, con ética y sentido social.'],
                    ['competencia', 'Elabora la historia clínica, el diagnóstico y el plan de tratamiento integral del paciente.'],
                    ['competencia', 'Realiza tratamientos de operatoria, periodoncia, endodoncia, prótesis y cirugía bucal de primer nivel.'],
                    ['competencia', 'Promueve la salud bucal en la comunidad mediante programas preventivos.'],
                    ['perfil_ingreso', 'Bachillerato concluido, interés por las ciencias de la salud, habilidad manual y vocación de servicio.'],
                    ['perfil_egreso', 'Cirujano dentista de práctica general que resuelve los problemas bucales de su comunidad y se actualiza de forma continua.'],
                ],
            ],
            [
                'maestria', 'MCE', 'Maestría en Ciencias Estomatológicas', 'POSG', null, 2, 'anios', 4,
                'Posgrado orientado a la investigación, reconocido en el padrón de posgrados de calidad del CONACYT (hoy CONAHCYT) desde 2012. Forma investigadores y docentes en las ciencias estomatológicas.',
                [
                    ['objetivo', 'Formar investigadores capaces de generar conocimiento original en las ciencias estomatológicas.'],
                    ['competencia', 'Diseña y conduce proyectos de investigación clínica y básica.'],
                    ['competencia', 'Publica y comunica resultados científicos.'],
                    ['perfil_ingreso', 'Título de licenciatura en odontología o área afín de la salud y promedio mínimo de 8.0.'],
                    ['perfil_egreso', 'Investigador que se integra a cuerpos académicos y a la docencia universitaria.'],
                ],
            ],
            [
                'especialidad', 'EPI', 'Especialidad en Periodoncia e Implantología', 'POSG', 'PERIO', 3, 'anios', 6,
                'Programa clínico de tres años para el diagnóstico, tratamiento y mantenimiento de las enfermedades periodontales y la rehabilitación con implantes dentales.',
                [
                    ['objetivo', 'Formar especialistas en periodoncia e implantología con sólidas bases científicas y clínicas.'],
                    ['competencia', 'Diagnostica y trata la enfermedad periodontal en todas sus etapas.'],
                    ['competencia', 'Planea y coloca implantes dentales y realiza procedimientos regenerativos.'],
                    ['perfil_ingreso', 'Título de Cirujano Dentista, cédula profesional y examen de admisión.'],
                    ['perfil_egreso', 'Especialista que resuelve casos periodontales e implantológicos complejos en equipo interdisciplinario.'],
                ],
            ],
            [
                'especialidad', 'EORT', 'Especialidad en Ortodoncia', 'POSG', 'ORTO', 3, 'anios', 6,
                'Especialidad de reciente creación para el diagnóstico y tratamiento de las maloclusiones y alteraciones del crecimiento craneofacial.',
                [
                    ['objetivo', 'Formar especialistas en ortodoncia y ortopedia dentofacial.'],
                    ['competencia', 'Realiza el diagnóstico cefalométrico y el plan de tratamiento ortodóncico.'],
                    ['competencia', 'Maneja aparatología fija y removible.'],
                    ['perfil_ingreso', 'Título de Cirujano Dentista, cédula profesional y examen de admisión.'],
                    ['perfil_egreso', 'Ortodoncista que atiende pacientes en crecimiento y adultos con ética y actualización constante.'],
                ],
            ],
            [
                'especialidad', 'EODP', 'Especialidad en Odontopediatría', 'POSG', 'ODPED', 2, 'anios', 4,
                'Especialidad de reciente creación para la atención integral de la salud bucal de niñas, niños y adolescentes.',
                [
                    ['objetivo', 'Formar especialistas en la atención bucal integral del paciente pediátrico.'],
                    ['competencia', 'Maneja la conducta del niño durante la consulta.'],
                    ['competencia', 'Realiza terapia pulpar, rehabilitación y ortodoncia interceptiva en dentición temporal y mixta.'],
                    ['perfil_ingreso', 'Título de Cirujano Dentista, cédula profesional y examen de admisión.'],
                    ['perfil_egreso', 'Odontopediatra que promueve la prevención desde la primera infancia.'],
                ],
            ],
        ];

        foreach ($programs as [$type, $code, $name, $dept, $spec, $duration, $unit, $periods, $description, $objectives]) {
            $program = AcademicProgram::create([
                'type' => $type,
                'code' => $code,
                'name' => $name,
                'department_id' => $department($dept),
                'specialty_id' => $specialty($spec),
                'description' => $description,
                'duration_value' => $duration,
                'duration_unit' => $unit,
                'total_periods' => $periods,
                'is_active' => true,
            ]);

            foreach ($objectives as $sort => [$objType, $text]) {
                $program->objectives()->create(['type' => $objType, 'description' => $text, 'sort' => $sort]);
            }

            $this->studyPlan($program);
        }

        $this->spaces();
    }

    private function studyPlan(AcademicProgram $program): void
    {
        $plan = StudyPlan::create([
            'academic_program_id' => $program->id,
            'name' => "Plan de estudios {$program->name}",
            'version' => '2023',
            'valid_from' => '2023-08-01',
            'description' => 'Plan de demostración: las materias son representativas, no el plan oficial.',
            'is_active' => true,
        ]);

        $sort = 0;

        foreach (self::PLANS[$program->code] as $period => $subjects) {
            $module = $plan->modules()->create([
                'name' => $program->type === 'licenciatura' ? "{$period}.º semestre" : "Semestre {$period}",
                'period_number' => $period,
                'sort' => $period,
            ]);

            foreach ($subjects as $index => [$name, $credits, $theory, $practice]) {
                $subject = Subject::create([
                    'code' => self::subjectCode($program->code, $period, $index),
                    'name' => $name,
                    'credits' => $credits,
                    'theory_hours' => $theory,
                    'practice_hours' => $practice,
                    'is_active' => true,
                ]);

                $plan->subjects()->attach($subject->id, [
                    'academic_module_id' => $module->id,
                    'period_number' => $period,
                    'is_required' => ! str_starts_with($name, 'Optativa'),
                    'sort' => $sort++,
                ]);
            }
        }
    }

    public static function subjectCode(string $program, int $period, int $index): string
    {
        return sprintf('%s-%02d%02d', $program, $period, $index + 1);
    }

    /** Aulas, laboratorios y espacios comunes (los consultorios van con cada clínica). */
    private function spaces(): void
    {
        $spaces = [];

        for ($n = 1; $n <= 8; $n++) {
            $spaces[] = ['aula', sprintf('AUL-A%02d', $n), "Aula A-{$n}", 'Edificio A', $n <= 4 ? 'PB' : '1', 40];
        }

        $spaces = [
            ...$spaces,
            ['aula', 'AUL-P01', 'Aula de posgrado 1', 'Edificio de posgrado', '1', 20],
            ['aula', 'AUL-P02', 'Aula de posgrado 2', 'Edificio de posgrado', '1', 20],
            ['laboratorio', 'LAB-SIM', 'Laboratorio de simulación clínica (fantomas)', 'Edificio A', '1', 30],
            ['laboratorio', 'LAB-PRO', 'Laboratorio de prótesis dental', 'Edificio A', '1', 30],
            ['laboratorio', 'LAB-CBA', 'Laboratorio de ciencias básicas', 'Edificio A', 'PB', 30],
            ['laboratorio', 'LAB-INV', 'Laboratorio de investigación estomatológica', 'Edificio de posgrado', 'PB', 12],
            ['auditorio', 'AUD-01', 'Auditorio de la Facultad', 'Edificio principal', 'PB', 250],
            ['otro', 'BIB-01', 'Biblioteca', 'Edificio principal', '1', 80],
            ['otro', 'CMP-01', 'Sala de cómputo', 'Edificio principal', '1', 30],
        ];

        foreach ($spaces as [$type, $code, $name, $building, $floor, $capacity]) {
            Space::create([
                'type' => $type, 'code' => $code, 'name' => $name, 'building' => $building,
                'floor' => $floor, 'capacity' => $capacity, 'is_active' => true,
            ]);
        }
    }

    /** El periodo vigente y el anterior (por si la base se sembró en otra fecha). */
    private function ensurePeriods(): void
    {
        $year = (int) today()->format('Y');

        foreach ([$year - 1, $year] as $periodYear) {
            AcademicPeriod::firstOrCreate(['code' => "{$periodYear}-A"], [
                'name' => "Enero - Junio {$periodYear}",
                'starts_at' => "{$periodYear}-01-15",
                'ends_at' => "{$periodYear}-06-30",
            ]);
            AcademicPeriod::firstOrCreate(['code' => "{$periodYear}-B"], [
                'name' => "Agosto - Diciembre {$periodYear}",
                'starts_at' => "{$periodYear}-08-01",
                'ends_at' => "{$periodYear}-12-15",
            ]);
        }

        if (! AcademicPeriod::current()) {
            AcademicPeriod::whereDate('starts_at', '<=', today())
                ->orderByDesc('starts_at')
                ->first()
                ?->forceFill(['is_current' => true])
                ->save();
        }
    }
}
