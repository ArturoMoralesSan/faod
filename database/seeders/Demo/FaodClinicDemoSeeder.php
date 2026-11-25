<?php

namespace Database\Seeders\Demo;

use App\Models\AcademicPeriod;
use App\Models\Clinic;
use App\Models\Department;
use App\Models\Space;
use App\Models\SpaceAvailability;
use App\Models\Specialty;
use Illuminate\Database\Seeder;

/**
 * Clínicas de la Facultad: la de licenciatura, las de posgrado y la de admisión
 * y radiología, con sus áreas, unidades dentales (consultorios), salas clínicas
 * para las prácticas de grupo y el equipo de cada una.
 */
class FaodClinicDemoSeeder extends Seeder
{
    use DemoHelpers;

    /** Clínica => [nombre, ubicación, descripción, unidades, [área => especialidad]]. */
    public const CLINICS = [
        'CLIN-LIC' => [
            'Clínica de Licenciatura', 'Edificio de clínicas, planta baja',
            'Clínica integral donde los alumnos de 5.º a 10.º semestre atienden a pacientes de la comunidad: diagnóstico, prevención, operatoria, periodoncia, endodoncia, prótesis y cirugía bucal, con supervisión docente.',
            16,
            ['Operatoria dental' => 'OPER', 'Periodoncia' => 'PERIO', 'Endodoncia' => 'ENDO', 'Prótesis' => 'PROTO', 'Cirugía bucal' => 'CIRMAX', 'Odontopediatría' => 'ODPED'],
        ],
        'CLIN-PERIO' => [
            'Clínica de Periodoncia e Implantología', 'Edificio de posgrado, planta baja',
            'Clínica de posgrado: tratamiento periodontal, cirugía periodontal, regeneración y colocación de implantes, atendidos por residentes de la especialidad.',
            6,
            ['Periodoncia' => 'PERIO', 'Implantología' => 'PERIO'],
        ],
        'CLIN-ORTO' => [
            'Clínica de Ortodoncia', 'Edificio de posgrado, primer piso',
            'Clínica de posgrado: diagnóstico y tratamiento de maloclusiones en niños, adolescentes y adultos.',
            6,
            ['Ortodoncia' => 'ORTO'],
        ],
        'CLIN-ODP' => [
            'Clínica de Odontopediatría', 'Edificio de posgrado, primer piso',
            'Atención de niñas, niños y adolescentes por residentes de la especialidad: prevención, terapia pulpar y rehabilitación.',
            5,
            ['Odontopediatría' => 'ODPED'],
        ],
        'CLIN-DX' => [
            'Clínica de Admisión, Diagnóstico y Radiología', 'Edificio de clínicas, planta baja',
            'Primer contacto del paciente: valoración inicial, historia clínica, radiografías y canalización a la clínica que corresponde.',
            3,
            ['Admisión y diagnóstico' => 'GRAL', 'Radiología' => 'GRAL'],
        ],
    ];

    public function run(): void
    {
        $departmentId = Department::firstWhere('code', 'CLIN')?->id;
        $period = AcademicPeriod::current();

        foreach (self::CLINICS as $code => [$name, $location, $description, $units, $areas]) {
            $clinic = Clinic::create([
                'department_id' => $departmentId,
                'code' => $code,
                'name' => $name,
                'location' => $location,
                'description' => $description,
                'is_active' => true,
            ]);

            $areaModels = collect($areas)->map(fn ($spec, $areaName) => $clinic->areas()->create([
                'name' => $areaName,
                'specialty_id' => Specialty::firstWhere('code', $spec)?->id,
                'is_active' => true,
            ]))->values();

            [$building, $floor] = str_contains($location, 'posgrado')
                ? ['Edificio de posgrado', str_contains($location, 'primer') ? '1' : 'PB']
                : ['Edificio de clínicas', 'PB'];

            for ($n = 1; $n <= $units; $n++) {
                $space = Space::create([
                    'clinic_id' => $clinic->id,
                    'clinic_area_id' => $areaModels[($n - 1) % $areaModels->count()]->id,
                    'type' => 'consultorio',
                    'code' => sprintf('%s-U%02d', $code, $n),
                    'name' => $code === 'CLIN-DX' && $n === $units ? 'Sala de rayos X' : sprintf('Unidad dental %02d', $n),
                    'building' => $building,
                    'floor' => $floor,
                    'capacity' => 1,
                    'is_active' => true,
                ]);

                // Atención: matutino y vespertino de lunes a viernes.
                foreach (range(1, 5) as $day) {
                    foreach ([[8, 14], [15, 19]] as [$start, $end]) {
                        SpaceAvailability::create([
                            'space_id' => $space->id,
                            'academic_period_id' => $period?->id,
                            'day_of_week' => $day,
                            'start_time' => $this->time($start),
                            'end_time' => $this->time($end),
                        ]);
                    }
                }

                $this->unitEquipment($clinic, $space, $n);
            }

            // Sala clínica para las prácticas de grupo (no se agenda en ella).
            if ($code !== 'CLIN-DX') {
                $halls = $code === 'CLIN-LIC' ? ['A', 'B'] : ['A'];

                foreach ($halls as $hall) {
                    Space::create([
                        'clinic_id' => $clinic->id,
                        'type' => 'otro',
                        'code' => "{$code}-S{$hall}",
                        'name' => "Sala clínica {$hall}",
                        'building' => $building,
                        'floor' => $floor,
                        'capacity' => $code === 'CLIN-LIC' ? 30 : 10,
                        'is_active' => true,
                    ]);
                }
            }

            $this->clinicEquipment($clinic);
        }
    }

    private function unitEquipment(Clinic $clinic, Space $space, int $n): void
    {
        $brand = $this->pick([['A-dec', '300'], ['Sirona', 'Intego'], ['Belmont', 'Eurus'], ['Dabi Atlante', 'Cart']]);
        $status = $this->chance(8) ? 'mantenimiento' : 'operativo';
        $lastMaintenance = today()->subDays(mt_rand(20, 170));

        $clinic->equipment()->create([
            'kind' => 'equipo',
            'space_id' => $space->id,
            'code' => sprintf('EQ-%s-UD%02d', substr($clinic->code, 5), $n),
            'name' => $space->name === 'Sala de rayos X' ? 'Equipo de rayos X periapical' : 'Unidad dental',
            'brand' => $brand[0],
            'model' => $brand[1],
            'serial_number' => strtoupper(substr(md5($space->code), 0, 10)),
            'status' => $status,
            'acquired_at' => today()->subYears(mt_rand(2, 9))->toDateString(),
            'last_maintenance_at' => $lastMaintenance->toDateString(),
            'next_maintenance_at' => $lastMaintenance->copy()->addMonths(6)->toDateString(),
            'notes' => $status === 'mantenimiento' ? 'Falla en la jeringa triple; en espera de refacción.' : null,
        ]);
    }

    private function clinicEquipment(Clinic $clinic): void
    {
        $short = substr($clinic->code, 5);

        $items = [
            ['equipo', 'AUT', 'Autoclave clase B', 'Tuttnauer', '2540EKA'],
            ['equipo', 'LFC', 'Lámpara de fotocurado LED', 'Woodpecker', 'LED-B'],
            ['equipo', 'CAV', 'Cavitrón ultrasónico', 'Dentsply', 'Cavitron Plus'],
            ['instrumental', 'KDX', 'Kit de diagnóstico (espejo, explorador, pinzas)', 'Hu-Friedy', null],
        ];

        $items = match ($clinic->code) {
            'CLIN-PERIO' => [...$items, ['equipo', 'MIM', 'Motor quirúrgico para implantes', 'NSK', 'Surgic Pro'], ['instrumental', 'KCP', 'Kit de cirugía periodontal', 'Hu-Friedy', null]],
            'CLIN-ORTO' => [...$items, ['instrumental', 'KOR', 'Kit de pinzas de ortodoncia', 'American Orthodontics', null]],
            'CLIN-DX' => [['equipo', 'OPG', 'Ortopantomógrafo digital', 'Carestream', 'CS 8100'], ['equipo', 'SEN', 'Sensor de radiografía digital', 'Carestream', 'RVG 6200'], ...$items],
            'CLIN-LIC' => [...$items, ['equipo', 'CMP', 'Compresor libre de aceite', 'Schulz', 'MSV 12'], ['instrumental', 'KEX', 'Kit de exodoncia (fórceps y elevadores)', 'Hu-Friedy', null]],
            default => $items,
        };

        foreach ($items as $i => [$kind, $code, $name, $brand, $model]) {
            $status = $i === 1 && $clinic->code === 'CLIN-LIC' ? 'fuera_servicio' : 'operativo';

            $clinic->equipment()->create([
                'kind' => $kind,
                'code' => "EQ-{$short}-{$code}",
                'name' => $name,
                'brand' => $brand,
                'model' => $model,
                'serial_number' => $kind === 'equipo' ? strtoupper(substr(md5($clinic->code.$code), 0, 10)) : null,
                'status' => $status,
                'acquired_at' => today()->subYears(mt_rand(1, 8))->toDateString(),
                'last_maintenance_at' => $kind === 'equipo' ? today()->subDays(mt_rand(15, 200))->toDateString() : null,
                'next_maintenance_at' => $kind === 'equipo' ? today()->addDays(mt_rand(-10, 150))->toDateString() : null,
                'notes' => $status === 'fuera_servicio' ? 'No enciende; se reportó a mantenimiento.' : null,
            ]);
        }
    }
}
