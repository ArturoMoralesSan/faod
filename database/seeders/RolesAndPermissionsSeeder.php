<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | Roles administrativos
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
        | Permisos
        |--------------------------------------------------------------------------
        */

        $permissions = [
            // Usuarios
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Roles
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',

            // Permisos
            'permissions.view',
            'permissions.create',
            'permissions.edit',
            'permissions.delete',

            // Personas
            'people.view',
            'people.create',
            'people.edit',
            'people.delete',

            // Configuración general
            'settings.view',
            'settings.edit',

            // Historial de cambios (solo consulta)
            'activity-log.view',

            // Reportes (cada uno exige además el permiso de consulta de su módulo)
            'reports.view',
        ];

        // Módulos con CRUD completo: modulo.view|create|edit|delete
        $crudModules = [
            'departments',
            'catalogs',
            'specialties',
            'academic-programs',
            'academic-periods',
            'cohorts',
            'subjects',
            'study-plans',
            'clinics',
            'spaces',
            'groups',
            'students',
            'knowledge-areas',
            'clinical-sessions',
            'clinic-incidents',
            'patients',
            'diagnosis-catalog',
            'diagnoses',
            'procedure-catalog',
            'treatments',
            'appointments',
            'inventory',
            'suppliers',
            'fee-concepts',
            'charges',
            'payments',
            'expenses',
            'activities',
        ];

        foreach ($crudModules as $module) {
            foreach (['view', 'create', 'edit', 'delete'] as $action) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        // Módulos sin alta/baja propia
        array_push(
            $permissions,
            'schedules.edit',
            'teachers.view',
            'teachers.edit',
            // Revisión docente de registros clínicos.
            'reviews.view',
            'reviews.create',
            // Horario propio del alumno y expediente propio del docente.
            'my-schedule.view',
            'my-record.view',
            // Caja: consultar cortes y operar (abrir, cerrar).
            'cash.view',
            'cash.operate',
        );

        /*
        |--------------------------------------------------------------------------
        | Crear permisos
        |--------------------------------------------------------------------------
        */

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | SuperAdmin
        |--------------------------------------------------------------------------
        |
        | El acceso total se administra mediante Gate::before().
        | No necesita permisos asignados directamente.
        |
        */

        $superadmin->syncPermissions([]);

        /*
        |--------------------------------------------------------------------------
        | Admin
        |--------------------------------------------------------------------------
        |
        | Puede administrar los módulos y registros del sistema.
        |
        */

        $admin->syncPermissions($permissions);

        /*
        |--------------------------------------------------------------------------
        | Roles de perfil
        |--------------------------------------------------------------------------
        |
        | PeopleController los asigna al crear perfiles de alumno, docente o
        | personal administrativo. Un usuario puede tener varios y elegir con
        | cuál navegar desde el selector de rol.
        |
        */

        $roles = [
            'alumno' => [
                // Solo su horario; no ve la gestión de grupos.
                'my-schedule.view',
                // Atiende pacientes en clínica bajo supervisión. El alta de
                // pacientes y de citas la hacen recepción y los docentes.
                'patients.view',
                'patients.edit',
                'diagnoses.view',
                'diagnoses.create',
                'diagnoses.edit',
                'treatments.view',
                'treatments.create',
                'treatments.edit',
                'appointments.view',
                'appointments.edit',
            ],
            // Solo consulta, y solo lo suyo (App\Support\TeacherScope): sus grupos,
            // los alumnos de esos grupos, los pacientes que supervisa, sus
            // clínicas y su propio expediente.
            'docente' => [
                'my-record.view',
                'groups.view',
                'students.view',
                'study-plans.view',
                'academic-programs.view',
                'clinics.view',
                'patients.view',
                // Para ver el expediente del paciente completo.
                'diagnoses.view',
                'treatments.view',
                'activities.view',
            ],
            'personal_administrativo' => [
                'people.view',
                'people.create',
                'people.edit',
                'students.view',
                'students.create',
                'students.edit',
                'teachers.view',
                'teachers.edit',
                'groups.view',
                'groups.create',
                'groups.edit',
                'schedules.edit',
                'cohorts.view',
                'academic-programs.view',
                'study-plans.view',
                'spaces.view',
                'clinics.view',
                'clinical-sessions.view',
                'clinical-sessions.create',
                'clinical-sessions.edit',
                'clinic-incidents.view',
                'clinic-incidents.create',
                'clinic-incidents.edit',
                'patients.view',
                'patients.create',
                'patients.edit',
                'appointments.view',
                'appointments.create',
                'appointments.edit',
                'appointments.delete',
                'inventory.view',
                'inventory.create',
                'inventory.edit',
                'suppliers.view',
                'suppliers.create',
                'suppliers.edit',
                'fee-concepts.view',
                'charges.view',
                'charges.create',
                'charges.edit',
                'payments.view',
                'payments.create',
                'expenses.view',
                'expenses.create',
                'expenses.edit',
                'cash.view',
                'cash.operate',
                'activities.view',
                'activities.create',
                'activities.edit',
            ],
        ];

        foreach ($roles as $name => $rolePermissions) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web'])
                ->givePermissionTo($rolePermissions);
        }

        // Alumno y docente quedan exactamente con su lista (se quitan los que se les retiraron).
        foreach (['alumno', 'docente'] as $name) {
            Role::findByName($name, 'web')->syncPermissions($roles[$name]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
