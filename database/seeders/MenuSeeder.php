<?php

namespace Database\Seeders;

use App\Models\Link;
use App\Models\Menu;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Panel (enlace simple, visible para todos)
        |--------------------------------------------------------------------------
        */

        Menu::updateOrCreate(
            ['name' => 'Panel'],
            [
                'icon' => 'LayoutDashboard',
                'order' => 0,
                'route' => 'admin.dashboard',
                'is_submenu' => false,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Configuración
        |--------------------------------------------------------------------------
        */

        $configuration = Menu::updateOrCreate(
            ['name' => 'Configuración'],
            [
                'icon' => 'Settings',
                'order' => 4,
                'route' => null,
                'is_submenu' => true,
            ]
        );

        $this->link(
            $configuration,
            'Usuarios',
            'Users',
            1,
            'admin.users.index',
            'users.view'
        );

        $this->link(
            $configuration,
            'Roles',
            'Shield',
            2,
            'admin.roles.index',
            'roles.view'
        );

        $this->link(
            $configuration,
            'Permisos',
            'KeyRound',
            3,
            'admin.permissions.index',
            'permissions.view'
        );

        $this->link($configuration, 'Departamentos', 'Building2', 4, 'admin.departments.index', 'departments.view');
        $this->link($configuration, 'Catálogos', 'ListTree', 5, 'admin.catalogs.index', 'catalogs.view');
        $this->link($configuration, 'Parámetros', 'Settings', 6, 'admin.settings.index', 'settings.view');

        /*
        |--------------------------------------------------------------------------
        | Académico
        |--------------------------------------------------------------------------
        */

        $academic = $this->menu('Académico', 'GraduationCap', 1);

        $this->link($academic, 'Programas', 'GraduationCap', 1, 'admin.academic-programs.index', 'academic-programs.view');
        $this->link($academic, 'Especialidades', 'Stethoscope', 2, 'admin.specialties.index', 'specialties.view');
        $this->link($academic, 'Generaciones', 'Users', 3, 'admin.cohorts.index', 'cohorts.view');
        $this->link($academic, 'Planes académicos', 'Library', 4, 'admin.study-plans.index', 'study-plans.view');
        $this->link($academic, 'Asignaturas', 'BookOpen', 5, 'admin.subjects.index', 'subjects.view');
        $this->link($academic, 'Periodos', 'CalendarDays', 6, 'admin.academic-periods.index', 'academic-periods.view');
        $this->link($academic, 'Grupos y horarios', 'CalendarClock', 7, 'admin.groups.index', 'groups.view');

        /*
        |--------------------------------------------------------------------------
        | Comunidad
        |--------------------------------------------------------------------------
        */

        $community = $this->menu('Comunidad', 'Users', 2);

        $this->link($community, 'Personas', 'Users', 1, 'admin.people.index', 'people.view');
        $this->link($community, 'Alumnos', 'GraduationCap', 2, 'admin.students.index', 'students.view');
        $this->link($community, 'Docentes', 'UserRoundCheck', 3, 'admin.teachers.index', 'teachers.view');
        $this->link($community, 'Áreas de conocimiento', 'BookOpen', 4, 'admin.knowledge-areas.index', 'knowledge-areas.view');

        /*
        |--------------------------------------------------------------------------
        | Clínicas
        |--------------------------------------------------------------------------
        */

        $clinics = $this->menu('Clínicas', 'Hospital', 3);

        $this->link($clinics, 'Clínicas', 'Hospital', 1, 'admin.clinics.index', 'clinics.view');
        $this->link($clinics, 'Aulas y espacios', 'DoorOpen', 2, 'admin.spaces.index', 'spaces.view');
        $this->link($clinics, 'Sesiones clínicas', 'CalendarCheck', 3, 'admin.clinical-sessions.index', 'clinical-sessions.view');
        $this->link($clinics, 'Incidencias', 'TriangleAlert', 4, 'admin.clinic-incidents.index', 'clinic-incidents.view');
    }

    private function menu(string $name, string $icon, int $order): Menu
    {
        return Menu::updateOrCreate(
            ['name' => $name],
            [
                'icon' => $icon,
                'order' => $order,
                'route' => null,
                'is_submenu' => true,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Crear o actualizar un enlace de menú
    |--------------------------------------------------------------------------
    */

    private function link(
        Menu $menu,
        string $name,
        string $icon,
        int $order,
        ?string $route,
        string $permission
    ): void {
        $permissionModel = Permission::where('name', $permission)
            ->where('guard_name', 'web')
            ->first();

        Link::updateOrCreate(
            [
                'menu_id' => $menu->id,
                'name' => $name,
            ],
            [
                'icon' => $icon,
                'order' => $order,
                'route' => $route,
                'permission_id' => $permissionModel?->id,
            ]
        );
    }
}
