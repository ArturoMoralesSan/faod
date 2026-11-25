<?php

namespace Database\Seeders;

use App\Models\AcademicProgram;
use App\Models\Setting;
use Database\Seeders\Demo\FaodAcademicDemoSeeder;
use Database\Seeders\Demo\FaodActivityDemoSeeder;
use Database\Seeders\Demo\FaodClinicDemoSeeder;
use Database\Seeders\Demo\FaodClinicOperationDemoSeeder;
use Database\Seeders\Demo\FaodFinanceDemoSeeder;
use Database\Seeders\Demo\FaodInventoryDemoSeeder;
use Database\Seeders\Demo\FaodPatientDemoSeeder;
use Database\Seeders\Demo\FaodPeopleDemoSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * SOLO PARA DEMOSTRACIÓN: datos de ejemplo de la Facultad de Odontología de la UJED.
 * No está en DatabaseSeeder; NO correrlo en producción.
 *
 * Reales (públicos): nombre, domicilio, programas académicos y organigrama.
 * Ficticios: personas, pacientes, planes de estudio, horarios, inventario y
 * movimientos. Las fechas se calculan a partir de hoy y del periodo vigente.
 *
 *   php artisan migrate:fresh --seed
 *   php artisan db:seed --class=FaodDemoSeeder
 *
 * Todos los usuarios de demostración entran con la contraseña 12345678:
 * docentes (@docentes.faod.test), alumnos (@alumnos.faod.test) y personal (@faod.test).
 */
class FaodDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('Los datos de demostración no se cargan en producción.');

            return;
        }

        if (! Role::where('name', 'docente')->exists()) {
            $this->command?->error('Primero corre los seeders base: php artisan db:seed');

            return;
        }

        if (AcademicProgram::where('code', 'LCD')->exists()) {
            $this->command?->warn('Los datos de demostración ya están cargados. Para recargarlos: php artisan migrate:fresh --seed y vuelve a correr este seeder.');

            return;
        }

        // Semilla fija: cada corrida genera las mismas personas y montos.
        mt_srand(20261124);

        DB::transaction(function () {
            $this->institution();

            $this->call([
                FaodAcademicDemoSeeder::class,
                FaodClinicDemoSeeder::class,
                FaodPeopleDemoSeeder::class,
                FaodClinicOperationDemoSeeder::class,
                FaodInventoryDemoSeeder::class,
                FaodPatientDemoSeeder::class,
                FaodFinanceDemoSeeder::class,
                FaodActivityDemoSeeder::class,
            ]);
        });

        mt_srand();
    }

    /** Datos de contacto públicos; solo se llenan si siguen vacíos. */
    private function institution(): void
    {
        $values = [
            'institution.address' => 'Predio Canoas s/n, Col. Los Ángeles, C.P. 34070, Victoria de Durango, Dgo.',
            'institution.phone' => '(618) 827 13 85',
            'institution.email' => 'contacto@faod.test',
        ];

        foreach ($values as $key => $value) {
            $setting = Setting::firstWhere('key', $key);

            if ($setting && blank($setting->value)) {
                $setting->update(['value' => $value]);
            }
        }
    }
}
