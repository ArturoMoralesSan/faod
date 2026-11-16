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
                'groups.view',
            ],
            'docente' => [
                'groups.view',
                'students.view',
                'teachers.view',
                'study-plans.view',
                'academic-programs.view',
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
            ],
        ];

        foreach ($roles as $name => $rolePermissions) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web'])
                ->givePermissionTo($rolePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
