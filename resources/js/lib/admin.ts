import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

import type { Option, PersonName, SelectOption } from '@/types/admin';
import labels from './labels.json';

/** Lista de valores (p. ej. ['aula','laboratorio']) a opciones de select con su etiqueta. */
export function enumOptions(values: string[]): SelectOption[] {
    return values.map((value) => ({ value, label: label(value) }));
}

/** Registros {id, name, code?} a opciones de select. */
export function recordOptions(records: Option[], withCode = false): SelectOption[] {
    return records.map((record) => ({
        value: record.id,
        label: withCode && record.code ? `${record.code} · ${record.name}` : record.name,
    }));
}

/**
 * Confirmación de eliminación con el mismo estilo de usuarios y roles.
 */
export function confirmDelete(
    title: string,
    text: string,
    url: string,
    options: { preserveState?: boolean } = {},
): void {
    Swal.fire({
        title,
        text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        confirmButtonColor: '#941B2B',
        cancelButtonColor: '#737982',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(url, {
                preserveScroll: true,
                preserveState: options.preserveState ?? false,
            });
        }
    });
}

export function fullName(person?: PersonName | null): string {
    if (!person) {
        return 'Sin nombre';
    }

    return [
        person.first_name,
        person.middle_name,
        person.paternal_surname,
        person.maternal_surname,
    ]
        .filter(Boolean)
        .join(' ');
}

/** Etiquetas legibles de valores de catálogo y estados (compartidas con PHP: App\Support\Labels). */
const LABELS: Record<string, string> = labels;

export function label(value?: string | null): string {
    if (!value) {
        return '—';
    }

    return LABELS[value] ?? value.replace(/[_-]/g, ' ');
}

export function formatDate(value?: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value.length === 10 ? `${value}T00:00:00` : value);

    return date.toLocaleDateString('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

/** Fecha ISO a YYYY-MM-DD para inputs type="date". */
export function toDateInput(value?: string | null): string {
    return value ? value.substring(0, 10) : '';
}

/** Fecha y hora ISO a "YYYY-MM-DDTHH:MM" (hora local) para inputs datetime-local. */
export function toDateTimeInput(value?: string | null): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

export function formatDateTime(value?: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

/** Cantidad sin ceros de más: 2.50 → "2.5", 3.00 → "3". */
export function formatQty(value?: number | string | null): string {
    return Number(value ?? 0).toLocaleString('es-MX', { maximumFractionDigits: 2 });
}

export function formatMoney(value?: number | string | null): string {
    return Number(value ?? 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
}

/** Edad en años a partir de la fecha de nacimiento. */
export function age(birthDate?: string | null): number | null {
    if (!birthDate) {
        return null;
    }

    const birth = new Date(`${birthDate.substring(0, 10)}T00:00:00`);
    const today = new Date();
    let years = today.getFullYear() - birth.getFullYear();

    if (today.getMonth() < birth.getMonth() || (today.getMonth() === birth.getMonth() && today.getDate() < birth.getDate())) {
        years--;
    }

    return years;
}

/** Fecha local de hoy en YYYY-MM-DD (sin desfase por zona horaria). */
export function todayInput(): string {
    return toDateTimeInput(new Date().toISOString()).substring(0, 10);
}

/** "08:00:00" → "08:00" */
export function formatTime(value?: string | null): string {
    return value ? value.substring(0, 5) : '';
}

export function formatSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}
