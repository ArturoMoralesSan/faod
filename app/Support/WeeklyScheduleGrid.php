<?php

namespace App\Support;

use App\Models\ClassSchedule;

/**
 * Acomoda bloques de horario en una semana para imprimirla: cada bloque queda
 * en su día con posición y alto proporcionales a su hora y duración.
 *
 * Cada bloque es ['day' => 1..7, 'start' => 'HH:MM', 'end' => 'HH:MM',
 * 'title' => string, 'lines' => string[], 'color' => int].
 */
class WeeklyScheduleGrid
{
    /** Colores de fondo y de acento que se van asignando por materia. */
    public const COLORS = [
        ['#F9ECEE', '#941B2B'],
        ['#E8F0FB', '#2A5DA8'],
        ['#E2F3E8', '#187744'],
        ['#FDF6E7', '#8A5A00'],
        ['#F1ECF9', '#6A3FA0'],
        ['#E6F5F5', '#1F7A7A'],
        ['#FBEDE6', '#A34A1C'],
        ['#F1F2F4', '#525960'],
    ];

    /** @var array<int, string> */
    public array $days = [];

    /** Primera y última hora completas que abarca la semana. */
    public int $firstHour = 8;

    public int $lastHour = 14;

    /**
     * Bloques por día, con su carril si se enciman (lane de lanes).
     *
     * @var array<int, array<int, array>>
     */
    public array $blocks = [];

    public function __construct(array $blocks)
    {
        $byDay = [];

        foreach ($blocks as $block) {
            $block['start'] = substr($block['start'], 0, 5);
            $block['end'] = substr($block['end'], 0, 5);
            $byDay[$block['day']][] = $block;
        }

        // Lunes a viernes siempre; sábado y domingo solo si tienen clases.
        $this->days = array_filter(
            ClassSchedule::DAYS,
            fn ($day) => $day <= 5 || isset($byDay[$day]),
            ARRAY_FILTER_USE_KEY,
        );

        if ($byDay) {
            $all = array_merge(...array_values($byDay));
            $this->firstHour = intdiv(min(array_map(fn ($b) => self::toMinutes($b['start']), $all)), 60);
            $this->lastHour = (int) ceil(max(array_map(fn ($b) => self::toMinutes($b['end']), $all)) / 60);
        }

        foreach ($byDay as $day => $dayBlocks) {
            $this->blocks[$day] = self::assignLanes($dayBlocks);
        }
    }

    public function isEmpty(): bool
    {
        return $this->blocks === [];
    }

    /** @return array<int, int> */
    public function hours(): array
    {
        return range($this->firstHour, $this->lastHour - 1);
    }

    /** Minutos desde la primera hora de la cuadrícula. */
    public function offset(string $time): int
    {
        return self::toMinutes($time) - $this->firstHour * 60;
    }

    public function totalMinutes(): int
    {
        return ($this->lastHour - $this->firstHour) * 60;
    }

    /**
     * Si dos bloques del mismo día se enciman, se reparten el ancho de la columna.
     */
    private static function assignLanes(array $blocks): array
    {
        usort($blocks, fn ($a, $b) => [$a['start'], $a['end']] <=> [$b['start'], $b['end']]);

        $result = [];
        $cluster = [];
        $clusterEnd = null;
        $laneEnds = [];

        $flush = function () use (&$result, &$cluster, &$laneEnds) {
            foreach ($cluster as $block) {
                $result[] = $block + ['lanes' => count($laneEnds)];
            }
            $cluster = [];
            $laneEnds = [];
        };

        foreach ($blocks as $block) {
            if ($clusterEnd !== null && $block['start'] >= $clusterEnd) {
                $flush();
                $clusterEnd = null;
            }

            $lane = 0;
            while (isset($laneEnds[$lane]) && $laneEnds[$lane] > $block['start']) {
                $lane++;
            }

            $laneEnds[$lane] = $block['end'];
            $cluster[] = $block + ['lane' => $lane];
            $clusterEnd = max($clusterEnd ?? $block['end'], $block['end']);
        }

        $flush();

        return $result;
    }

    public static function toMinutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        return $h * 60 + $m;
    }

    public static function minutes(string $start, string $end): int
    {
        return self::toMinutes($end) - self::toMinutes($start);
    }

    /** @return array{0: string, 1: string} */
    public static function color(int $key): array
    {
        return self::COLORS[$key % count(self::COLORS)];
    }
}
