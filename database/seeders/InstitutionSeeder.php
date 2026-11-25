<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Parámetros generales y estructura organizacional base.
 * Ajusta nombres y claves a la estructura real de la facultad.
 */
class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Parámetros generales
        |--------------------------------------------------------------------------
        |
        | updateOrCreate solo sobre la clave: si ya existe, NO se sobrescribe el
        | valor que el administrador haya cambiado desde la pantalla.
        |
        */

        $settings = [
            // Institución
            ['general', 'institution.name', 'Facultad de Odontología', 'Nombre de la institución'],
            ['general', 'institution.university', 'Universidad Juárez del Estado de Durango', 'Universidad'],
            ['general', 'institution.phone', '', 'Teléfono'],
            ['general', 'institution.email', '', 'Correo de contacto'],
            ['general', 'institution.address', '', 'Domicilio'],

            // Agenda
            ['agenda', 'appointments.default_minutes', 60, 'Duración de cita (minutos)'],
            ['agenda', 'appointments.reminder_hours', 24, 'Recordatorio antes de la cita (horas)'],

            // Alumnos
            ['alumnos', 'students.require_university_proof', true, 'Exigir comprobante de inscripción'],

            // Inventario
            ['inventario', 'inventory.expiry_alert_days', 30, 'Avisar caducidad con (días) de anticipación'],
        ];

        foreach ($settings as [$group, $key, $value, $label]) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['group' => $group, 'value' => $value, 'label' => $label]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Departamentos y coordinaciones
        |--------------------------------------------------------------------------
        */

        // Estructura según el Plan de Desarrollo FAOD 2019-2025.
        $direccion = $this->department('DIR', 'Dirección', 'departamento');
        $this->department('SGEN', 'Secretaría General', 'departamento', $direccion);
        $academica = $this->department('SAC', 'Secretaría Académica', 'departamento', $direccion);
        $administrativa = $this->department('SAD', 'Secretaría Administrativa', 'departamento', $direccion);
        $this->department('DEPI', 'División de Estudios de Posgrado e Investigación', 'departamento', $direccion);

        $this->department('POSG', 'Coordinación de Posgrado', 'coordinacion', $academica);
        $this->department('CLIN', 'Coordinación de Clínicas', 'coordinacion', $academica);
        $this->department('EDUC', 'Coordinación de Educación Continua y Seguimiento de Egresados', 'coordinacion', $academica);
        $this->department('TUT', 'Coordinación de Tutorías', 'coordinacion', $academica);
        $this->department('BIB', 'Coordinación Académica de Biblioteca', 'coordinacion', $academica);
        $this->department('SSO', 'Coordinación de Servicio Social', 'coordinacion', $academica);
        $this->department('ESC', 'Coordinación de Servicios Escolares', 'coordinacion', $academica);
        $this->department('VINC', 'Coordinación de Vinculación', 'coordinacion', $academica);
        $this->department('PSI', 'Coordinación de Psicología', 'coordinacion', $academica);
        $this->department('ALM', 'Almacén', 'coordinacion', $administrativa);
        $this->department('CAJA', 'Caja', 'coordinacion', $administrativa);
    }

    private function department(string $code, string $name, string $type, ?Department $parent = null): Department
    {
        return Department::updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'type' => $type,
                'parent_id' => $parent?->id,
                'is_active' => true,
            ]
        );
    }
}
