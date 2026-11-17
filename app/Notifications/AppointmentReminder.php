<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Recordatorio de cita para el paciente.
 */
class AppointmentReminder extends Notification
{
    use Queueable;

    public function __construct(public Appointment $appointment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appointment = $this->appointment->loadMissing(['patient.people', 'clinic', 'space']);
        $date = $appointment->date->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY');
        $time = substr($appointment->start_time, 0, 5);

        return (new MailMessage)
            ->subject('Recordatorio de cita – Facultad de Odontología')
            ->greeting('Hola, '.$appointment->patient->people->first_name)
            ->line("Te recordamos tu cita el {$date} a las {$time} h.")
            ->line('Clínica: '.$appointment->clinic->name.($appointment->space ? " · {$appointment->space->name}" : ''))
            ->when($appointment->reason, fn (MailMessage $mail) => $mail->line("Motivo: {$appointment->reason}"))
            ->line('Si no puedes asistir, avísanos con anticipación para reprogramarla.')
            ->salutation('Facultad de Odontología');
    }
}
