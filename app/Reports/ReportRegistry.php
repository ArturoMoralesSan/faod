<?php

namespace App\Reports;

use App\Models\User;
use App\Reports\Definitions;
use Illuminate\Support\Collection;

/**
 * Catálogo de reportes disponibles.
 */
class ReportRegistry
{
    /** Orden en que aparecen en el catálogo. */
    public const REPORTS = [
        Definitions\StudentsReport::class,
        Definitions\ActivityParticipantsReport::class,
        Definitions\PatientsReport::class,
        Definitions\ProceduresReport::class,
        Definitions\StudentProductivityReport::class,
        Definitions\ClinicalSessionsReport::class,
        Definitions\IncidentsReport::class,
        Definitions\AppointmentsReport::class,
        Definitions\StockReport::class,
        Definitions\MovementsReport::class,
        Definitions\PaymentsReport::class,
        Definitions\ChargesReport::class,
        Definitions\ExpensesReport::class,
    ];

    /** @return Collection<int, Report> */
    public static function all(): Collection
    {
        return collect(self::REPORTS)->map(fn (string $class) => app($class));
    }

    /** @return Collection<int, Report> */
    public static function availableFor(User $user): Collection
    {
        return self::all()->filter(fn (Report $report) => $user->can($report->permission()))->values();
    }

    public static function find(string $key): ?Report
    {
        return self::all()->first(fn (Report $report) => $report->key() === $key);
    }
}
