<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro del historial de cambios.
 */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    public const EVENTS = ['created', 'updated', 'deleted', 'restored'];

    protected $table = 'activity_log';

    protected $fillable = ['user_id', 'event', 'subject_type', 'subject_id', 'subject_label', 'changes', 'ip_address'];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Nombre legible de cada tipo de registro (para filtros y la vista).
     *
     * @return array<class-string, string>
     */
    public static function subjectNames(): array
    {
        return [
            User::class => 'Usuario',
            People::class => 'Persona',
            StudentEnrollment::class => 'Inscripción de alumno',
            TeacherProfile::class => 'Docente',
            Clinic::class => 'Clínica',
            ClinicIncident::class => 'Incidencia',
            ClinicalSession::class => 'Sesión clínica',
            Patient::class => 'Paciente',
            PatientDiagnosis::class => 'Diagnóstico',
            TreatmentPlan::class => 'Plan de tratamiento',
            TreatmentProcedure::class => 'Procedimiento',
            Appointment::class => 'Cita',
            InventoryItem::class => 'Artículo de inventario',
            InventoryCount::class => 'Conteo físico',
            Charge::class => 'Adeudo',
            Payment::class => 'Pago',
            Expense::class => 'Gasto',
            CashRegisterSession::class => 'Turno de caja',
            AcademicActivity::class => 'Actividad académica',
        ];
    }
}
