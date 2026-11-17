<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recordatorios de las citas de mañana (requiere `php artisan schedule:run` en el cron del servidor).
Schedule::command('appointments:send-reminders')->dailyAt('09:00');
