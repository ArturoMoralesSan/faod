import { router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

/**
 * Búsqueda y filtros de las vistas Index (mismo comportamiento que usuarios).
 */
export function useIndexFilters<T extends Record<string, string>>(
    url: () => string,
    initial: T,
) {
    const defaults = Object.fromEntries(
        Object.keys(initial).map((key) => [key, '']),
    ) as T;

    const filters = reactive({ ...initial }) as T;

    const clean = () =>
        Object.fromEntries(
            Object.entries(filters)
                .map(([key, value]) => [key, String(value ?? '').trim()])
                .filter(([, value]) => value !== ''),
        );

    const hasFilters = computed(() => Object.keys(clean()).length > 0);

    const submit = (): void => {
        router.get(url(), clean(), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const clear = (): void => {
        Object.assign(filters, defaults);
        submit();
    };

    return { filters, hasFilters, submit, clear };
}

/** Convierte un filtro del backend (número, null) a string para los selects. */
export function filterValue(value: unknown): string {
    return value === null || value === undefined ? '' : String(value);
}
