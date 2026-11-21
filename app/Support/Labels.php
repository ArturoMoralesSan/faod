<?php

namespace App\Support;

/**
 * Etiquetas legibles de valores de catálogo y estados.
 *
 * Usa el mismo archivo que el frontend (resources/js/lib/labels.json),
 * para que pantallas, PDF y CSV muestren lo mismo.
 */
class Labels
{
    /** @var array<string, string>|null */
    private static ?array $labels = null;

    public static function get(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        self::$labels ??= json_decode(file_get_contents(resource_path('js/lib/labels.json')), true) ?: [];

        return self::$labels[$value] ?? ucfirst(str_replace(['_', '-'], ' ', $value));
    }

    /** Mismos grupos de color que resources/js/components/admin/StatusBadge.vue. */
    private const POSITIVE = ['activo', 'active', 'inscrito', 'en_curso', 'egresado', 'operativo', 'concluida', 'asistio', 'resuelta', 'cerrada', 'autorizado', 'concluido', 'realizado', 'confirmada', 'atendida', 'pagado', 'acreditado', 'becado', 'vigente', 'suficiente', 'entrada'];

    private const NEGATIVE = ['baja', 'inactive', 'no_inscrito', 'fuera_servicio', 'cancelada', 'falta', 'abierta', 'rechazado', 'cancelado', 'suspendido', 'no_asistio', 'no_acreditado', 'vencido', 'sin_existencia', 'critica'];

    private const PENDING = ['pendiente', 'planeada', 'programada', 'mantenimiento', 'en_proceso', 'justificada', 'correcciones', 'planeado', 'parcial', 'bajo_minimo'];

    /** Tono de color de un estado: positive, negative, warning o neutral. */
    public static function tone(string $value): string
    {
        return match (true) {
            in_array($value, self::POSITIVE, true) => 'positive',
            in_array($value, self::NEGATIVE, true) => 'negative',
            in_array($value, self::PENDING, true) => 'warning',
            default => 'neutral',
        };
    }

    /**
     * Valores a opciones de select [{value, label}].
     *
     * @param  array<int, string>  $values
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(array $values): array
    {
        return array_map(fn ($value) => ['value' => $value, 'label' => self::get($value)], $values);
    }
}
