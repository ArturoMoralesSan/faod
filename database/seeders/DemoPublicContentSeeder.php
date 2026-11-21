<?php

namespace Database\Seeders;

use App\Models\AcademicActivity;
use App\Models\AcademicProgram;
use App\Models\Clinic;
use App\Models\Space;
use App\Models\Specialty;
use App\Models\StudyPlan;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * SOLO PARA DEMOSTRACIÓN: contenido de ejemplo para ver el sitio público con datos.
 * No está en DatabaseSeeder; NO correrlo en producción.
 *
 *   Crear:  php artisan db:seed --class=DemoPublicContentSeeder
 *   Borrar: php artisan tinker --execute="Database\Seeders\DemoPublicContentSeeder::remove();"
 *
 * Todos los registros llevan el prefijo DEMO- en su clave para poder borrarlos.
 */
class DemoPublicContentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $specialty = fn (string $code) => Specialty::firstWhere('code', $code)?->id;

            $programs = [
                ['licenciatura', 'DEMO-LCD', 'Licenciatura de Cirujano Dentista (demo)', null, 5, 'anios', 10, 'Forma profesionales capaces de diagnosticar, prevenir y tratar las enfermedades bucales más frecuentes, con práctica clínica supervisada desde los primeros semestres.'],
                ['especialidad', 'DEMO-EORT', 'Especialidad en Ortodoncia (demo)', 'ORTO', 3, 'anios', 6, 'Programa clínico para el diagnóstico y tratamiento de maloclusiones y alteraciones del crecimiento craneofacial.'],
                ['especialidad', 'DEMO-EEND', 'Especialidad en Endodoncia (demo)', 'ENDO', 2, 'anios', 4, 'Formación en el tratamiento de la pulpa dental y los tejidos periapicales con técnicas actuales.'],
                ['especialidad', 'DEMO-EODP', 'Especialidad en Odontopediatría (demo)', 'ODPED', 2, 'anios', 4, 'Atención integral de la salud bucal de niñas, niños y adolescentes.'],
                ['maestria', 'DEMO-MCO', 'Maestría en Ciencias Odontológicas (demo)', null, 2, 'anios', 4, 'Posgrado orientado a la investigación clínica y la docencia en odontología.'],
                ['diplomado', 'DEMO-DEST', 'Diplomado en Odontología Estética (demo)', 'OPER', 6, 'meses', 1, 'Actualización en resinas, carillas y blanqueamiento para odontólogos de práctica general.'],
            ];

            foreach ($programs as [$type, $code, $name, $spec, $duration, $unit, $periods, $description]) {
                $program = AcademicProgram::updateOrCreate(['code' => $code], [
                    'type' => $type, 'name' => $name, 'specialty_id' => $spec ? $specialty($spec) : null,
                    'description' => $description, 'duration_value' => $duration, 'duration_unit' => $unit,
                    'total_periods' => $periods, 'is_active' => true,
                ]);

                $program->objectives()->delete();
                foreach ([
                    ['objetivo', 'Formar especialistas con sólidas bases científicas y clínicas.'],
                    ['competencia', 'Elabora diagnósticos y planes de tratamiento integrales.'],
                    ['competencia', 'Trabaja en equipo con otras áreas de la salud.'],
                    ['perfil_ingreso', 'Título de Cirujano Dentista y cédula profesional.'],
                    ['perfil_egreso', 'Resuelve casos clínicos de su área con ética y sentido humano.'],
                ] as $i => [$objType, $text]) {
                    $program->objectives()->create(['type' => $objType, 'description' => $text, 'sort' => $i]);
                }
            }

            // Plan de estudios de ejemplo para la especialidad en Ortodoncia.
            $ortho = AcademicProgram::firstWhere('code', 'DEMO-EORT');
            $plan = StudyPlan::updateOrCreate(['academic_program_id' => $ortho->id, 'version' => 'DEMO'], ['name' => 'Plan de estudios (demo)', 'is_active' => true]);
            $plan->subjects()->detach();
            $subjects = [
                [1, 'Crecimiento y desarrollo craneofacial', 8], [1, 'Biomecánica ortodóncica', 8], [1, 'Clínica de ortodoncia I', 12],
                [2, 'Cefalometría', 6], [2, 'Clínica de ortodoncia II', 12], [2, 'Seminario de investigación', 4],
                [3, 'Ortopedia dentofacial', 8], [3, 'Clínica de ortodoncia III', 12],
                [4, 'Ortodoncia quirúrgica', 6], [4, 'Clínica de ortodoncia IV', 12],
            ];
            foreach ($subjects as $i => [$period, $name, $credits]) {
                $subject = Subject::updateOrCreate(['code' => sprintf('DEMO-ORT%02d', $i + 1)], ['name' => $name, 'credits' => $credits, 'theory_hours' => 32, 'practice_hours' => str_contains($name, 'Clínica') ? 96 : 16, 'is_active' => true]);
                $plan->subjects()->attach($subject->id, ['period_number' => $period, 'is_required' => ! str_contains($name, 'Seminario'), 'sort' => $i]);
            }

            // Clínicas.
            foreach ([
                ['DEMO-CLP', 'Clínica de Licenciatura (demo)', 'Edificio principal, planta baja', 'Atención general: prevención, operatoria, periodoncia básica y exodoncias.', ['Operatoria' => 'OPER', 'Periodoncia' => 'PERIO']],
                ['DEMO-CPG', 'Clínica de Posgrado (demo)', 'Edificio de posgrado', 'Tratamientos de especialidad atendidos por residentes con supervisión docente.', ['Ortodoncia' => 'ORTO', 'Endodoncia' => 'ENDO', 'Odontopediatría' => 'ODPED']],
            ] as [$code, $name, $location, $description, $areas]) {
                $clinic = Clinic::updateOrCreate(['code' => $code], ['name' => $name, 'location' => $location, 'description' => $description, 'is_active' => true]);
                foreach ($areas as $areaName => $spec) {
                    $clinic->areas()->updateOrCreate(['name' => $areaName], ['specialty_id' => $specialty($spec), 'is_active' => true]);
                }
                for ($n = 1; $n <= 4; $n++) {
                    Space::updateOrCreate(['code' => "{$code}-C{$n}"], ['clinic_id' => $clinic->id, 'type' => 'consultorio', 'name' => "Consultorio {$n}", 'is_active' => true]);
                }
            }

            // Actividades.
            foreach ([
                ['congreso', 'Congreso de Odontología (demo)', 25, 3, 20, 1500, 250, 'Conferencias magistrales, presentación de casos clínicos y exposición comercial.'],
                ['curso', 'Curso de fotografía clínica dental (demo)', 12, 1, 16, 1200, 25, 'Técnicas de fotografía intraoral y extraoral para documentar casos.'],
                ['taller', 'Taller de resinas en el sector anterior (demo)', 40, 1, 8, 900, 15, 'Práctica en modelos con estratificación de resinas.'],
                ['jornada', 'Jornada de salud bucal comunitaria (demo)', -20, 1, 6, null, null, 'Revisión, limpieza y aplicación de flúor gratuitas para la comunidad.'],
                ['seminario', 'Seminario de investigación en odontología (demo)', -45, 2, 10, null, 60, 'Presentación de avances de tesis de posgrado.'],
            ] as [$type, $title, $offset, $days, $hours, $cost, $capacity, $description]) {
                AcademicActivity::updateOrCreate(['title' => $title], [
                    'type' => $type, 'description' => $description, 'modality' => 'presencial',
                    'starts_at' => today()->addDays($offset), 'ends_at' => today()->addDays($offset + $days - 1),
                    'schedule' => '9:00 a 14:00', 'location' => 'Auditorio de la facultad', 'hours' => $hours, 'cost' => $cost,
                    'capacity' => $capacity, 'status' => $offset < 0 ? 'concluida' : 'abierta',
                    'issues_certificate' => true, 'min_attendance' => 80,
                    'results' => $offset < 0 ? 'Actividad realizada con buena participación.' : null,
                ]);
            }
        });
    }

    /** Borra todo lo que creó este seeder. */
    public static function remove(): void
    {
        DB::transaction(function () {
            AcademicActivity::where('title', 'like', '%(demo)')->forceDelete();
            Space::where('code', 'like', 'DEMO-%')->forceDelete();
            Clinic::where('code', 'like', 'DEMO-%')->get()->each(fn ($c) => tap($c->areas()->delete(), fn () => $c->forceDelete()));
            StudyPlan::where('version', 'DEMO')->get()->each(fn ($p) => tap($p->subjects()->detach(), fn () => $p->forceDelete()));
            Subject::where('code', 'like', 'DEMO-%')->forceDelete();
            AcademicProgram::where('code', 'like', 'DEMO-%')->get()->each(fn ($p) => tap($p->objectives()->delete(), fn () => $p->forceDelete()));
        });
    }
}
