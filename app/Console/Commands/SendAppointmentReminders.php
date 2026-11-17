<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Notifications\AppointmentReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Envía por correo el recordatorio de las citas del día siguiente.
 */
class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders {--days=1 : Días de anticipación}';

    protected $description = 'Envía recordatorios por correo de las citas próximas';

    public function handle(): int
    {
        $date = today()->addDays((int) $this->option('days'))->toDateString();

        $appointments = Appointment::query()
            ->whereDate('date', $date)
            ->whereIn('status', ['programada', 'confirmada'])
            ->whereNull('reminder_sent_at')
            ->whereHas('patient.people', fn ($q) => $q->whereNotNull('personal_email'))
            ->with('patient.people')
            ->get();

        foreach ($appointments as $appointment) {
            Notification::route('mail', $appointment->patient->people->personal_email)
                ->notify(new AppointmentReminder($appointment));

            $appointment->update(['reminder_sent_at' => now()]);
        }

        $this->info("Recordatorios enviados: {$appointments->count()} (citas del {$date}).");

        return self::SUCCESS;
    }
}
