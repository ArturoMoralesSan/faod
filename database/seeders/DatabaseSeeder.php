<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Acceso: permisos y roles antes del menú (los enlaces guardan permission_id).
            RolesAndPermissionsSeeder::class,
            MenuSeeder::class,

            // Datos base del sistema.
            CatalogSeeder::class,
            InstitutionSeeder::class,
            AcademicSeeder::class,

            // Usuarios al final: el de prueba necesita los roles docente y alumno.
            UserSeeder::class,
        ]);
    }
}
