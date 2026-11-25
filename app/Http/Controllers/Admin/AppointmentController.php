<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AppointmentRequest;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Space;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\TreatmentPlan;
use App\Notifications\AppointmentReminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Agenda de citas: semana por clínica, lista con filtros y acciones
 * (confirmar, atender, inasistencia, cancelar, reprogramar, recordar).
 */
class AppointmentController extends Controller
{
    public function index(Request $request): Response
    {
        $view = in_array($request->input('view'), ['day', 'week', 'month', 'list'], true) ? $request->input('view') : 'week';
        $people = ':id,first_name,middle_name,paternal_surname,maternal_surname,phone,personal_email';

        $query = Appointment::query()
            ->with([
                "patient.people{$people}",
                'clinic:id,name',
                'space:id,code,name',
                'student.people:id,first_name,paternal_surname',
                'teacher.people:id,first_name,paternal_surname',
            ])
            ->when($request->clinic_id, fn ($q, $id) => $q->where('clinic_id', $id))
            ->when($request->space_id, fn ($q, $id) => $q->where('space_id', $id))
            ->when($request->student_profile_id, fn ($q, $id) => $q->where('student_profile_id', $id))
            ->when($request->teacher_profile_id, fn ($q, $id) => $q->where('teacher_profile_id', $id))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->search, fn ($q, $search) => $q->whereHas('patient', fn ($q) => $q
                ->where('record_number', 'like', "%{$search}%")
                ->orWhereHas('people', fn ($q) => $q->search($search))));

        try {
            $date = Carbon::parse($request->input('date', today()->toDateString()))->startOfDay();
        } catch (\Throwable) {
            $date = today();
        }

        $weekStart = $date->copy()->startOfWeek();

        // Día, semana o mes: todas las citas del rango, en orden cronológico.
        $range = match ($view) {
            'day' => [$date, $date],
            'week' => [$weekStart, $weekStart->copy()->addDays(6)],
            'month' => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()],
            default => null,
        };

        if ($range) {
            $appointments = $query
                ->whereDate('date', '>=', $range[0]->toDateString())
                ->whereDate('date', '<=', $range[1]->toDateString())
                ->orderBy('date')
                ->orderBy('start_time')
                ->get();
        } else {
            $appointments = $query
                ->when($request->date_from, fn ($q, $date) => $q->whereDate('date', '>=', $date))
                ->when($request->date_to, fn ($q, $date) => $q->whereDate('date', '<=', $date))
                ->orderByDesc('date')
                ->orderByDesc('start_time')
                ->paginate(20)
                ->withQueryString();
        }

        return Inertia::render('admin/appointments/Index', [
            'view' => $view,
            'appointments' => $appointments,
            'date' => $date->toDateString(),
            'weekStart' => $weekStart->toDateString(),
            'filters' => $request->only('clinic_id', 'space_id', 'student_profile_id', 'teacher_profile_id', 'status', 'search', 'date_from', 'date_to'),
            'statuses' => Appointment::STATUSES,
            'clinics' => Clinic::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'spaces' => Space::whereNotNull('clinic_id')->where('is_active', true)->orderBy('name')->get(['id', 'clinic_id', 'code', 'name']),
            'students' => $this->students(),
            'teachers' => $this->teachers(),
            'can' => [
                'create' => $request->user()->can('appointments.create'),
                'edit' => $request->user()->can('appointments.edit'),
                'delete' => $request->user()->can('appointments.delete'),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $patient = $request->integer('patient_id')
            ? Patient::with('people:id,first_name,middle_name,paternal_surname,maternal_surname')->find($request->integer('patient_id'))
            : null;

        return Inertia::render('admin/appointments/Create', [
            ...$this->formData($patient?->id),
            'defaults' => [
                ...$request->only('clinic_id', 'space_id', 'date', 'start_time', 'student_profile_id'),
                'patient_id' => $patient?->id,
                'patient_label' => $patient ? "{$patient->record_number} · {$patient->people->full_name}" : null,
                'clinic_id' => $request->input('clinic_id', $patient?->clinic_id),
            ],
        ]);
    }

    public function store(AppointmentRequest $request): RedirectResponse
    {
        Appointment::create([
            ...$request->validated(),
            'status' => 'programada',
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.appointments.index', ['date' => $request->input('date'), 'clinic_id' => $request->input('clinic_id')])
            ->with('success', 'Cita agendada correctamente.');
    }

    public function edit(Appointment $appointment): Response|RedirectResponse
    {
        if ($appointment->isClosed()) {
            return back()->with('error', 'La cita ya está cerrada y no se puede editar.');
        }

        $appointment->load('patient.people:id,first_name,middle_name,paternal_surname,maternal_surname');

        return Inertia::render('admin/appointments/Edit', [
            'appointment' => $appointment,
            'patientLabel' => "{$appointment->patient->record_number} · {$appointment->patient->people->full_name}",
            ...$this->formData($appointment->patient_id),
        ]);
    }

    public function update(AppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        abort_if($appointment->isClosed(), 422, 'La cita ya está cerrada.');

        $appointment->update($request->validated());

        return redirect()
            ->route('admin.appointments.index', ['date' => $appointment->date->toDateString(), 'clinic_id' => $appointment->clinic_id])
            ->with('success', 'Cita actualizada.');
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        $appointment->delete();

        return back()->with('success', 'Cita eliminada.');
    }

    /* ------------------------------- Acciones ------------------------------- */

    public function confirm(Appointment $appointment): RedirectResponse
    {
        return $this->transition($appointment, ['programada'], 'confirmada', ['confirmed_at' => now()], 'Cita confirmada.');
    }

    public function attend(Appointment $appointment): RedirectResponse
    {
        return $this->transition($appointment, ['programada', 'confirmada'], 'atendida', [], 'Cita marcada como atendida.');
    }

    public function noShow(Appointment $appointment): RedirectResponse
    {
        return $this->transition($appointment, ['programada', 'confirmada'], 'no_asistio', [], 'Inasistencia registrada.');
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validate(['cancellation_reason' => ['required', 'string', 'max:255']]);

        return $this->transition($appointment, ['programada', 'confirmada'], 'cancelada', [
            'cancelled_at' => now(),
            'cancellation_reason' => $data['cancellation_reason'],
        ], 'Cita cancelada.');
    }

    /**
     * Crea la nueva cita y marca la original como reprogramada.
     */
    public function reschedule(AppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        if (! in_array($appointment->status, ['programada', 'confirmada', 'no_asistio'], true)) {
            return back()->with('error', 'Solo se reprograman citas programadas, confirmadas o con inasistencia.');
        }

        DB::transaction(function () use ($request, $appointment) {
            Appointment::create([
                ...$request->validated(),
                'status' => 'programada',
                'rescheduled_from_id' => $appointment->id,
                'created_by' => $request->user()->id,
            ]);

            if ($appointment->status !== 'no_asistio') {
                $appointment->update(['status' => 'reprogramada']);
            }
        });

        return redirect()
            ->route('admin.appointments.index', ['date' => $request->input('date'), 'clinic_id' => $request->input('clinic_id')])
            ->with('success', 'Cita reprogramada.');
    }

    /**
     * Envía el recordatorio por correo (si el paciente tiene) y lo marca como enviado.
     * Sin correo, solo se registra (p. ej. después de llamar por teléfono).
     */
    public function remind(Appointment $appointment): RedirectResponse
    {
        $email = $appointment->patient->people?->personal_email;

        if ($email) {
            Notification::route('mail', $email)->notify(new AppointmentReminder($appointment));
        }

        $appointment->update(['reminder_sent_at' => now()]);

        return back()->with('success', $email ? "Recordatorio enviado a {$email}." : 'Recordatorio registrado (el paciente no tiene correo).');
    }

    private function transition(Appointment $appointment, array $from, string $to, array $extra, string $message): RedirectResponse
    {
        if (! in_array($appointment->status, $from, true)) {
            return back()->with('error', 'La cita no se puede cambiar a ese estado desde "'.str_replace('_', ' ', $appointment->status).'".');
        }

        $appointment->update(['status' => $to, ...$extra]);

        return back()->with('success', $message);
    }

    private function students()
    {
        return StudentProfile::query()
            ->whereHas('enrollments', fn ($q) => $q->where('status', 'activo'))
            ->with('people:id,first_name,paternal_surname,maternal_surname')
            ->get(['id', 'people_id', 'registration_number']);
    }

    private function teachers()
    {
        return TeacherProfile::where('status', 'active')
            ->with('people:id,first_name,paternal_surname,maternal_surname')
            ->get(['id', 'people_id']);
    }

    private function formData(?int $patientId): array
    {
        return [
            'clinics' => Clinic::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'spaces' => Space::whereNotNull('clinic_id')->where('is_active', true)->orderBy('name')->get(['id', 'clinic_id', 'code', 'name']),
            'students' => $this->students(),
            'teachers' => $this->teachers(),
            'treatmentPlans' => $patientId
                ? TreatmentPlan::where('patient_id', $patientId)
                    ->whereIn('status', ['en_proceso'])
                    ->get(['id', 'patient_id', 'title'])
                : [],
            'patientSearchUrl' => route('admin.patients.search'),
        ];
    }
}
