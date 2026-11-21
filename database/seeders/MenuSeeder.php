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
                // Siempre al final del menú.
                'order' => 20,
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
        $this->link($configuration, 'Historial de cambios', 'FileClock', 7, 'admin.activity-log.index', 'activity-log.view');

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
        $this->link($academic, 'Cursos y actividades', 'Presentation', 8, 'admin.activities.index', 'activities.view');

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

        /*
        |--------------------------------------------------------------------------
        | Atención clínica
        |--------------------------------------------------------------------------
        */

        $care = $this->menu('Atención clínica', 'Stethoscope', 3);

        $this->link($care, 'Pacientes', 'Contact', 1, 'admin.patients.index', 'patients.view');
        $this->link($care, 'Revisiones pendientes', 'ClipboardList', 2, 'admin.reviews.index', 'reviews.view');
        $this->link($care, 'Catálogo de diagnósticos', 'ListTree', 3, 'admin.diagnoses.index', 'diagnosis-catalog.view');
        $this->link($care, 'Catálogo de procedimientos', 'ClipboardPlus', 4, 'admin.procedures.index', 'procedure-catalog.view');

        /*
        |--------------------------------------------------------------------------
        | Clínicas
        |--------------------------------------------------------------------------
        */

        $clinics = $this->menu('Clínicas', 'Hospital', 4);

        /*
        |--------------------------------------------------------------------------
        | Agenda
        |--------------------------------------------------------------------------
        */

        $agenda = $this->menu('Agenda', 'CalendarRange', 5);

        $this->link($agenda, 'Citas', 'CalendarRange', 1, 'admin.appointments.index', 'appointments.view');

        /*
        |--------------------------------------------------------------------------
        | Inventario
        |--------------------------------------------------------------------------
        */

        $inventory = $this->menu('Inventario', 'Package', 6);

        $this->link($inventory, 'Artículos y existencias', 'Package', 1, 'admin.inventory.index', 'inventory.view');
        $this->link($inventory, 'Movimientos', 'ScrollText', 2, 'admin.inventory.movements.index', 'inventory.view');
        $this->link($inventory, 'Inventario físico', 'ClipboardList', 3, 'admin.inventory-counts.index', 'inventory.view');
        $this->link($inventory, 'Almacenes', 'Boxes', 4, 'admin.warehouses.index', 'inventory.view');
        $this->link($inventory, 'Proveedores', 'Truck', 5, 'admin.suppliers.index', 'suppliers.view');

        /*
        |--------------------------------------------------------------------------
        | Finanzas
        |--------------------------------------------------------------------------
        */

        $finance = $this->menu('Finanzas', 'Wallet', 7);

        $this->link($finance, 'Adeudos', 'Receipt', 1, 'admin.finance.charges.index', 'charges.view');
        $this->link($finance, 'Pagos', 'Banknote', 2, 'admin.finance.payments.index', 'payments.view');
        $this->link($finance, 'Caja y cortes', 'Wallet', 3, 'admin.finance.cash-registers.index', 'cash.view');
        $this->link($finance, 'Gastos', 'HandCoins', 4, 'admin.finance.expenses.index', 'expenses.view');
        $this->link($finance, 'Conceptos y cuotas', 'Tag', 5, 'admin.finance.concepts.index', 'fee-concepts.view');

        $this->link($clinics, 'Clínicas', 'Hospital', 1, 'admin.clinics.index', 'clinics.view');
        $this->link($clinics, 'Aulas y espacios', 'DoorOpen', 2, 'admin.spaces.index', 'spaces.view');
        $this->link($clinics, 'Sesiones clínicas', 'CalendarCheck', 3, 'admin.clinical-sessions.index', 'clinical-sessions.view');
        $this->link($clinics, 'Incidencias', 'TriangleAlert', 4, 'admin.clinic-incidents.index', 'clinic-incidents.view');

        $reports = $this->menu('Reportes', 'FileChartColumn', 8);

        $this->link($reports, 'Todos los reportes', 'FileChartColumn', 1, 'admin.reports.index', 'reports.view');
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
