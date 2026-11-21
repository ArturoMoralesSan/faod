<?php

namespace App\Reports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Definición de un reporte: filtros, consulta, columnas e indicadores.
 *
 * La misma definición alimenta la vista previa, el PDF y el CSV.
 */
abstract class Report
{
    /** Clave opcional en row(): true si el renglón se muestra pero no suma a los totales (p. ej. un pago cancelado). */
    public const EXCLUDE_FROM_TOTALS = '_exclude_from_totals';

    /** Identificador para la URL, p. ej. "pagos". */
    abstract public function key(): string;

    abstract public function title(): string;

    abstract public function description(): string;

    /** Grupo del catálogo de reportes (Académico, Finanzas, ...). */
    abstract public function section(): string;

    /** Permiso necesario además de reports.view. */
    abstract public function permission(): string;

    /** @return array<int, Column> */
    abstract public function columns(): array;

    /** Consulta ya filtrada y ordenada. */
    abstract public function query(array $filters): Builder;

    /** Valores sin formato de una fila, por clave de columna. */
    abstract public function row(Model $record): array;

    public function icon(): string
    {
        return 'FileChartColumn';
    }

    /** Hoja horizontal en el PDF (reportes con muchas columnas). */
    public function landscape(): bool
    {
        return count($this->columns()) > 7;
    }

    /**
     * Filtros disponibles.
     *
     * @return array<int, array{key: string, label: string, type: string, options?: array, default?: string|null, placeholder?: string}>
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * Indicadores del encabezado, calculados sobre todos los registros filtrados.
     *
     * @return array<int, array{label: string, value: string, tone?: string}>
     */
    public function summary(Builder $query, array $filters): array
    {
        return [['label' => 'Registros', 'value' => Column::formatNumber($query->count())]];
    }

    /* ------------------------------------------------------------------ */
    /* Ayudas para definir filtros                                         */
    /* ------------------------------------------------------------------ */

    /**
     * Rango de fechas (date_from / date_to). Por omisión, el mes en curso.
     */
    protected static function period(string $label = 'Periodo', ?string $default = 'month'): array
    {
        return ['key' => 'period', 'label' => $label, 'type' => 'period', 'default' => $default];
    }

    /** @param  array<int, array{value: string|int, label: string}>  $options */
    protected static function select(string $key, string $label, array $options, string $placeholder = 'Todos'): array
    {
        return ['key' => $key, 'label' => $label, 'type' => 'select', 'options' => array_values($options), 'placeholder' => $placeholder];
    }

    protected static function search(string $placeholder = 'Buscar...'): array
    {
        return ['key' => 'search', 'label' => 'Búsqueda', 'type' => 'search', 'placeholder' => $placeholder];
    }

    /**
     * Registros {id, name, code?} a opciones de select.
     *
     * @return array<int, array{value: int, label: string}>
     */
    protected static function records(iterable $records, bool $withCode = false): array
    {
        $options = [];

        foreach ($records as $record) {
            $options[] = [
                'value' => $record->id,
                'label' => $withCode && $record->code ? "{$record->code} · {$record->name}" : $record->name,
            ];
        }

        return $options;
    }

    /* ------------------------------------------------------------------ */
    /* Lectura de filtros                                                  */
    /* ------------------------------------------------------------------ */

    /**
     * Valores de filtro válidos tomados de la petición. Lo que no es válido se descarta.
     *
     * @return array<string, string>
     */
    public function resolveFilters(Request $request): array
    {
        $values = [];

        foreach ($this->filters() as $filter) {
            if ($filter['type'] === 'period') {
                [$from, $to] = $this->resolvePeriod($request, $filter['default'] ?? null);
                $values += array_filter(['date_from' => $from, 'date_to' => $to]);

                continue;
            }

            $value = trim((string) $request->query($filter['key'], ''));

            if ($value === '') {
                continue;
            }

            if ($filter['type'] === 'select') {
                $allowed = array_map(fn ($option) => (string) $option['value'], $filter['options']);

                if (! in_array($value, $allowed, true)) {
                    continue;
                }
            }

            $values[$filter['key']] = mb_substr($value, 0, 100);
        }

        return $values;
    }

    /** @return array{0: ?string, 1: ?string} */
    private function resolvePeriod(Request $request, ?string $default): array
    {
        // "Todo" explícito desde la pantalla: sin límite de fechas.
        if ($request->query('date_from') === null && $request->query('date_to') === null && ! $request->boolean('all_dates')) {
            return match ($default) {
                'month' => [today()->startOfMonth()->toDateString(), today()->toDateString()],
                'year' => [today()->startOfYear()->toDateString(), today()->toDateString()],
                default => [null, null],
            };
        }

        $from = $this->date($request->query('date_from'));
        $to = $this->date($request->query('date_to'));

        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Filtros aplicados en texto, para el encabezado del PDF.
     *
     * @return array<int, string>
     */
    public function describeFilters(array $values): array
    {
        $lines = [];

        foreach ($this->filters() as $filter) {
            if ($filter['type'] === 'period') {
                $from = isset($values['date_from']) ? Carbon::parse($values['date_from'])->format('d/m/Y') : null;
                $to = isset($values['date_to']) ? Carbon::parse($values['date_to'])->format('d/m/Y') : null;

                $lines[] = $filter['label'].': '.match (true) {
                    $from && $to => "{$from} al {$to}",
                    (bool) $from => "desde {$from}",
                    (bool) $to => "hasta {$to}",
                    default => 'todas las fechas',
                };

                continue;
            }

            if (! isset($values[$filter['key']])) {
                continue;
            }

            $value = $values[$filter['key']];

            if ($filter['type'] === 'select') {
                $option = collect($filter['options'])->first(fn ($option) => (string) $option['value'] === $value);
                $value = $option['label'] ?? $value;
            }

            $lines[] = "{$filter['label']}: {$value}";
        }

        return $lines;
    }

    /** Aplica date_from / date_to a una columna de fecha. */
    protected function wherePeriod(Builder $query, string $column, array $filters): Builder
    {
        return $query
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate($column, '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate($column, '<=', $date));
    }

    /** Porcentaje redondeado (0 si no hay base). */
    protected static function percent(float $part, float $whole): string
    {
        return $whole > 0 ? Column::formatNumber(round($part * 100 / $whole, 1)).'%' : '—';
    }

    protected static function money(float $value): string
    {
        return '$'.number_format($value, 2);
    }

    /** Número de registros por valor de una columna, p. ej. por estado. */
    protected static function countBy(Builder $query, string $column): array
    {
        return (clone $query)
            ->reorder()
            ->toBase()
            // select() reemplaza las columnas (p. ej. withCount) que romperían el GROUP BY en MySQL.
            ->select(DB::raw("{$column} as grouped_value"))
            ->selectRaw('count(*) as aggregate')
            ->groupBy($column)
            ->pluck('aggregate', 'grouped_value')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /** Ficha del catálogo. */
    public function toArray(): array
    {
        return [
            'key' => $this->key(),
            'title' => $this->title(),
            'description' => $this->description(),
            'section' => $this->section(),
            'icon' => $this->icon(),
        ];
    }
}
