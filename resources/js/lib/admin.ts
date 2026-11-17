import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

import type { Option, PersonName, SelectOption } from '@/types/admin';

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

const LABELS: Record<string, string> = {
    // Departamentos
    departamento: 'Departamento',
    coordinacion: 'Coordinación',

    // Programas
    especialidad: 'Especialidad',
    maestria: 'Maestría',
    doctorado: 'Doctorado',
    diplomado: 'Diplomado',
    posdoctorado: 'Posdoctorado',
    licenciatura: 'Licenciatura',
    semestres: 'Semestres',
    cuatrimestres: 'Cuatrimestres',
    anios: 'Años',
    meses: 'Meses',
    semanas: 'Semanas',
    coordinador: 'Coordinador',
    docente: 'Docente',
    tutor: 'Tutor',
    objetivo: 'Objetivo',
    competencia: 'Competencia',
    perfil_ingreso: 'Perfil de ingreso',
    perfil_egreso: 'Perfil de egreso',

    // Generaciones
    planeada: 'Planeada',
    en_curso: 'En curso',
    concluida: 'Concluida',

    // Espacios
    aula: 'Aula',
    laboratorio: 'Laboratorio',
    consultorio: 'Consultorio',
    auditorio: 'Auditorio',
    otro: 'Otro',

    // Alumnos
    activo: 'Activo',
    baja: 'Baja',
    egresado: 'Egresado',
    inscrito: 'Inscrito',
    no_inscrito: 'No inscrito',
    pendiente: 'Pendiente',

    // Clínicas
    responsable: 'Responsable',
    supervisor: 'Supervisor',
    operativo: 'Operativo',
    mantenimiento: 'En mantenimiento',
    fuera_servicio: 'Fuera de servicio',

    // Sesiones clínicas
    programada: 'Programada',
    cancelada: 'Cancelada',
    asistio: 'Asistió',
    falta: 'Falta',
    justificada: 'Justificada',

    // Incidencias
    equipo: 'Equipo',
    instalaciones: 'Instalaciones',
    bioseguridad: 'Bioseguridad',
    seguridad: 'Seguridad',
    paciente: 'Paciente',
    media: 'Media',
    alta: 'Alta',
    critica: 'Crítica',
    abierta: 'Abierta',
    en_proceso: 'En proceso',
    resuelta: 'Resuelta',
    cerrada: 'Cerrada',

    // Perfiles
    active: 'Activo',
    inactive: 'Inactivo',

    // Roles
    SuperAdmin: 'Super administrador',
    admin: 'Administrador',
    alumno: 'Alumno',
    personal_administrativo: 'Personal administrativo',
};

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
