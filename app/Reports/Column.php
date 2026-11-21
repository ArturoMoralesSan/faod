<?php

namespace App\Reports;

use App\Support\Labels;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Columna de un reporte: cómo se titula, se formatea y si se totaliza.
 */
class Column
{
    public const TYPES = ['text', 'date', 'datetime', 'time', 'money', 'number', 'percent', 'status'];

    public string $type = 'text';

    public bool $total = false;

    /** Columna secundaria: en el PDF va con letra más tenue. */
    public bool $muted = false;

    public function __construct(public string $key, public string $label) {}

    public static function make(string $key, string $label): self
    {
        return new self($key, $label);
    }

    public function date(): self
    {
        return $this->as('date');
    }

    public function datetime(): self
    {
        return $this->as('datetime');
    }

    public function money(bool $total = true): self
    {
        $this->total = $total;

        return $this->as('money');
    }

    public function number(bool $total = false): self
    {
        $this->total = $total;

        return $this->as('number');
    }

    public function percent(): self
    {
        return $this->as('percent');
    }

    public function status(): self
    {
        return $this->as('status');
    }

    public function muted(): self
    {
        $this->muted = true;

        return $this;
    }

    public function as(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function numeric(): bool
    {
        return in_array($this->type, ['money', 'number', 'percent'], true);
    }

    /** Texto para pantalla y PDF. */
    public function format(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($this->type) {
            'date' => $this->carbon($value)->format('d/m/Y'),
            'datetime' => $this->carbon($value)->format('d/m/Y H:i'),
            'time' => substr((string) $value, 0, 5),
            'money' => '$'.number_format((float) $value, 2),
            'number' => self::formatNumber((float) $value),
            'percent' => self::formatNumber((float) $value).'%',
            'status' => Labels::get((string) $value),
            default => (string) $value,
        };
    }

    /** Valor para CSV: números sin formato para que Excel pueda sumarlos. */
    public function csv(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return match ($this->type) {
            'money' => number_format((float) $value, 2, '.', ''),
            'number', 'percent' => (string) (float) $value,
            default => $this->format($value),
        };
    }

    public function toArray(): array
    {
        return ['key' => $this->key, 'label' => $this->label, 'type' => $this->type, 'total' => $this->total];
    }

    public static function formatNumber(float $value): string
    {
        return number_format($value, fmod($value, 1.0) === 0.0 ? 0 : 2);
    }

    private function carbon(mixed $value): CarbonInterface
    {
        return $value instanceof CarbonInterface ? $value : Carbon::parse($value);
    }
}
