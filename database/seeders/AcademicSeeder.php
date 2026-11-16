<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\KnowledgeArea;
use App\Models\Specialty;
use Illuminate\Database\Seeder;

/**
 * Catálogos académicos base: especialidades, áreas de conocimiento y periodos.
 */
class AcademicSeeder extends Seeder
{
    public function run(): void
    {
        $specialties = [
            'ORTO' => 'Ortodoncia',
            'ENDO' => 'Endodoncia',
            'PERIO' => 'Periodoncia',
            'ODPED' => 'Odontopediatría',
            'PROTO' => 'Prostodoncia',
            'CIRMAX' => 'Cirugía Oral y Maxilofacial',
            'REHAB' => 'Rehabilitación Oral',
            'OPER' => 'Operatoria Dental',
            'GRAL' => 'Odontología General',
        ];

        foreach ($specialties as $code => $name) {
            Specialty::updateOrCreate(['code' => $code], ['name' => $name]);
        }

        $areas = [
            'Biomateriales',
            'Anatomía y fisiología',
            'Farmacología',
            'Patología bucal',
            'Radiología e imagenología',
            'Investigación',
            'Salud pública',
            'Docencia',
        ];

        foreach ($areas as $name) {
            KnowledgeArea::firstOrCreate(['name' => $name]);
        }

        /*
        |--------------------------------------------------------------------------
        | Periodos
        |--------------------------------------------------------------------------
        |
        | Se marca como vigente el periodo que contiene la fecha de hoy.
        |
        */

        $year = (int) now()->format('Y');

        foreach ([$year, $year + 1] as $periodYear) {
            AcademicPeriod::updateOrCreate(
                ['code' => "{$periodYear}-A"],
                [
                    'name' => "Enero - Junio {$periodYear}",
                    'starts_at' => "{$periodYear}-01-15",
                    'ends_at' => "{$periodYear}-06-30",
                ]
            );

            AcademicPeriod::updateOrCreate(
                ['code' => "{$periodYear}-B"],
                [
                    'name' => "Agosto - Diciembre {$periodYear}",
                    'starts_at' => "{$periodYear}-08-01",
                    'ends_at' => "{$periodYear}-12-15",
                ]
            );
        }

        if (! AcademicPeriod::where('is_current', true)->exists()) {
            $current = AcademicPeriod::whereDate('starts_at', '<=', now())
                ->orderByDesc('starts_at')
                ->first();

            $current?->forceFill(['is_current' => true])->save();
        }
    }
}
