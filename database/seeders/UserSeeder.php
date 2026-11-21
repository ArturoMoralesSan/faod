<?php

namespace Database\Seeders;

use App\Models\People;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Obtener / crear roles administrativos
        |--------------------------------------------------------------------------
        */

        $superadmin = Role::firstOrCreate([
            'name' => 'SuperAdmin',
            'guard_name' => 'web',
        ]);

        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Super administrador
        |--------------------------------------------------------------------------
        */

        $superadminUser = User::updateOrCreate(
            [
                'email' => 'ramon.morales41@gmail.com',
            ],
            [
                'name' => 'Ramón Morales',
                'password' => Hash::make('12345678'),
            ]
        );

        $this->ensureQrToken($superadminUser);

        $superadminUser->syncRoles([$superadmin]);

        /*
        |--------------------------------------------------------------------------
        | Administrador
        |--------------------------------------------------------------------------
        |
        | Cambia el nombre, correo y contraseña por los datos definitivos.
        |
        */

        $adminUser = User::updateOrCreate(
            [
                'email' => 'admin@odontologia.test',
            ],
            [
                'name' => 'Administrador',
                'password' => Hash::make('Admin12345'),
            ]
        );

        $this->ensureQrToken($adminUser);

        $adminUser->syncRoles([$admin]);

        /*
        |--------------------------------------------------------------------------
        | Usuario de prueba con varios roles (solo local)
        |--------------------------------------------------------------------------
        |
        | Docente y alumno a la vez, para probar el selector de rol del menú.
        |
        */

        if (app()->environment('local')) {
            $this->seedMultiRoleUser();
        }
    }

    private function seedMultiRoleUser(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'docente.alumno@odontologia.test'],
            [
                'name' => 'Laura Gómez Rivera',
                'password' => Hash::make('12345678'),
            ]
        );

        $this->ensureQrToken($user);

        $person = People::updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => 'Laura',
                'paternal_surname' => 'Gómez',
                'maternal_surname' => 'Rivera',
                'personal_email' => $user->email,
                'country' => 'México',
            ]
        );

        $teacher = $person->teacherProfile()->updateOrCreate(
            ['people_id' => $person->id],
            [
                'professional_title' => 'Cirujana Dentista',
                'status' => 'active',
            ]
        );

        // El grado máximo se registra en "Títulos y grados".
        $teacher->degrees()->firstOrCreate(
            ['level' => 'especialidad'],
            ['title' => 'Especialidad', 'institution' => 'Universidad Juárez del Estado de Durango']
        );

        $person->studentProfile()->updateOrCreate(
            ['people_id' => $person->id],
            [
                'registration_number' => 'DEMO-0001',
                'status' => 'active',
            ]
        );

        $roles = Role::whereIn('name', ['docente', 'alumno'])
            ->where('guard_name', 'web')
            ->pluck('name')
            ->all();

        $user->syncRoles($roles);
    }

    /*
    |--------------------------------------------------------------------------
    | Generar QR token si el usuario no tiene uno
    |--------------------------------------------------------------------------
    */

    private function ensureQrToken(User $user): void
    {
        if (! $user->qr_token) {
            do {
                $token = Str::random(64);
            } while (
                User::where('qr_token', $token)->exists()
            );

            $user->update([
                'qr_token' => $token,
            ]);
        }
    }
}
