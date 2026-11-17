<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicGroup;
use App\Models\AcademicPeriod;
use App\Models\Appointment;
use App\Models\Charge;
use App\Models\Payment;
use App\Models\InventoryItem;
use App\Models\InventoryLot;
use App\Models\Patient;
use App\Models\Review;
use App\Models\ClinicalSession;
use App\Models\ClinicEquipment;
use App\Models\ClinicIncident;
use App\Models\StudentEnrollment;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Panel de inicio: indicadores y listas según los permisos del usuario.
 */
class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $can = fn (string $permission) => $user->can($permission);

        return Inertia::render('Dashboard', [
            'period' => AcademicPeriod::current()?->only('id', 'name'),
            'stats' => array_values(array_filter($this->stats($can))),
            'todayAppointments' => $can('appointments.view')
                ? Appointment::query()
                    ->whereDate('date', today())
                    ->whereIn('status', ['programada', 'confirmada', 'atendida'])
                    ->with([
                        'patient.people:id,first_name,paternal_surname,maternal_surname',
                        'clinic:id,name',
                        'space:id,code',
                    ])
                    ->orderBy('start_time')
                    ->limit(10)
                    ->get()
                : null,
            'todaySessions' => $can('clinical-sessions.view')
                ? ClinicalSession::query()
                    ->whereDate('date', today())
                    ->where('status', '!=', 'cancelada')
                    ->with(['clinic:id,name', 'space:id,code,name', 'group:id,code', 'supervisor.people:id,first_name,paternal_surname'])
                    ->orderBy('start_time')
                    ->limit(8)
                    ->get()
                : null,
            'openIncidents' => $can('clinic-incidents.view')
                ? ClinicIncident::query()
                    ->whereNotIn('status', ClinicIncident::CLOSED_STATUSES)
                    ->with('clinic:id,name')
                    ->latest('occurred_at')
                    ->limit(6)
                    ->get(['id', 'clinic_id', 'title', 'severity', 'status', 'occurred_at'])
                : null,
            'maintenanceDue' => $can('clinics.view')
                ? ClinicEquipment::query()
                    ->whereNotIn('status', ['baja'])
                    ->whereDate('next_maintenance_at', '<=', today()->addDays(15))
                    ->with('clinic:id,name')
                    ->orderBy('next_maintenance_at')
                    ->limit(6)
                    ->get(['id', 'clinic_id', 'code', 'name', 'next_maintenance_at'])
                : null,
        ]);
    }

    /**
     * Indicadores visibles para el usuario (null = sin permiso).
     *
     * @return array<int, array{label: string, value: int, url: string, icon: string, tone?: string}|null>
     */
    private function stats(callable $can): array
    {
        $periodId = AcademicPeriod::current()?->id;

        return [
            $can('students.view') ? [
                'label' => 'Alumnos activos',
                'value' => StudentEnrollment::where('status', 'activo')->distinct('student_profile_id')->count('student_profile_id'),
                'url' => route('admin.students.index'),
                'icon' => 'GraduationCap',
            ] : null,
            $can('teachers.view') ? [
                'label' => 'Docentes activos',
                'value' => TeacherProfile::where('status', 'active')->count(),
                'url' => route('admin.teachers.index'),
                'icon' => 'UserRoundCheck',
            ] : null,
            $can('groups.view') && $periodId ? [
                'label' => 'Grupos del periodo',
                'value' => AcademicGroup::where('academic_period_id', $periodId)->count(),
                'url' => route('admin.groups.index'),
                'icon' => 'CalendarClock',
            ] : null,
            $can('appointments.view') ? [
                'label' => 'Citas de hoy',
                'value' => Appointment::whereDate('date', today())->whereIn('status', Appointment::ACTIVE_STATUSES)->count(),
                'url' => route('admin.appointments.index'),
                'icon' => 'CalendarRange',
            ] : null,
            $can('reviews.view') ? [
                'label' => 'Revisiones pendientes',
                'value' => collect(Review::REVIEWABLE_TYPES)->sum(fn ($class) => $class::where('review_status', 'pendiente')->count()),
                'url' => route('admin.reviews.index'),
                'icon' => 'ClipboardList',
                'tone' => 'alert',
            ] : null,
            $can('patients.view') ? [
                'label' => 'Pacientes activos',
                'value' => Patient::where('status', 'activo')->count(),
                'url' => route('admin.patients.index'),
                'icon' => 'Contact',
            ] : null,
            $can('clinical-sessions.view') ? [
                'label' => 'Sesiones clínicas hoy',
                'value' => ClinicalSession::whereDate('date', today())->where('status', '!=', 'cancelada')->count(),
                'url' => route('admin.clinical-sessions.index'),
                'icon' => 'CalendarCheck',
            ] : null,
            $can('clinic-incidents.view') ? [
                'label' => 'Incidencias abiertas',
                'value' => ClinicIncident::whereNotIn('status', ClinicIncident::CLOSED_STATUSES)->count(),
                'url' => route('admin.clinic-incidents.index'),
                'icon' => 'TriangleAlert',
                'tone' => 'alert',
            ] : null,
            $can('inventory.view') ? [
                'label' => 'Artículos bajo el mínimo',
                'value' => InventoryItem::lowStock()->count(),
                'url' => route('admin.inventory.index', ['alert' => 'low']),
                'icon' => 'Package',
                'tone' => 'alert',
            ] : null,
            $can('inventory.view') ? [
                'label' => 'Lotes por caducar',
                'value' => InventoryLot::expiringWithin(InventoryItemController::EXPIRATION_ALERT_DAYS)->count(),
                'url' => route('admin.inventory.index', ['alert' => 'expiring']),
                'icon' => 'TriangleAlert',
                'tone' => 'alert',
            ] : null,
            $can('payments.view') ? [
                'label' => 'Cobrado hoy',
                'value' => (int) round(Payment::whereNull('cancelled_at')->whereDate('paid_at', today())->sum('amount')),
                'url' => route('admin.finance.payments.index', ['date_from' => today()->toDateString()]),
                'icon' => 'Banknote',
                'format' => 'money',
            ] : null,
            $can('charges.view') ? [
                'label' => 'Adeudos vencidos',
                'value' => (int) round((float) Charge::overdue()->selectRaw('coalesce(sum(amount - discount_amount - paid_amount), 0) as total')->value('total')),
                'url' => route('admin.finance.charges.index', ['status' => 'vencido']),
                'icon' => 'Receipt',
                'tone' => 'alert',
                'format' => 'money',
            ] : null,
            $can('users.view') ? [
                'label' => 'Cuentas desactivadas',
                'value' => User::where('is_active', false)->count(),
                'url' => route('admin.users.index'),
                'icon' => 'Users',
            ] : null,
        ];
    }
}
