/** Tipos y utilidades del sitio público. */

export interface ProgramCard {
    code: string;
    name: string;
    type: string;
    description: string | null;
    duration_value: number | null;
    duration_unit: string | null;
    total_periods: number | null;
    specialty: string | null;
}

export interface LevelGroup {
    level: string;
    programs: ProgramCard[];
}

export interface ActivityCard {
    id: number;
    title: string;
    type: string;
    modality: string;
    status: string;
    starts_at: string | null;
    ends_at: string | null;
    hours: number | null;
    cost: number | null;
    capacity: number | null;
    available: number | null;
    issues_certificate: boolean;
    place: string | null;
    summary: string | null;
}

export interface SpecialtyCard {
    code: string;
    name: string;
    description: string | null;
    programs: number;
    program_code: string | null;
}

export interface ClinicCard {
    code: string;
    name: string;
    description: string | null;
    location: string | null;
    offices: number;
    areas: { name: string; specialty: string | null }[];
}

/** Nombre del nivel en plural para los títulos de sección. */
export const LEVEL_TITLES: Record<string, { title: string; text: string }> = {
    licenciatura: { title: 'Licenciatura', text: 'Formación profesional en odontología integral.' },
    especialidad: { title: 'Especialidades', text: 'Posgrados clínicos para profundizar en un área de la odontología.' },
    maestria: { title: 'Maestrías', text: 'Formación avanzada para la investigación y la docencia.' },
    doctorado: { title: 'Doctorados', text: 'El grado más alto, orientado a la generación de conocimiento.' },
    diplomado: { title: 'Diplomados', text: 'Actualización profesional de menor duración.' },
};

const UNITS: Record<string, [string, string]> = {
    semestres: ['semestre', 'semestres'],
    cuatrimestres: ['cuatrimestre', 'cuatrimestres'],
    anios: ['año', 'años'],
    meses: ['mes', 'meses'],
    semanas: ['semana', 'semanas'],
};

export function duration(value: number | null, unit: string | null): string | null {
    if (!value || !unit) {
        return null;
    }

    const [one, many] = UNITS[unit] ?? [unit, unit];

    return `${value} ${value === 1 ? one : many}`;
}

/** Nombre del periodo del plan de estudios (semestre 1, cuatrimestre 2…). */
export function periodName(unit: string | null, period: number): string {
    const one = unit && UNITS[unit] && ['semestres', 'cuatrimestres'].includes(unit) ? UNITS[unit][0] : 'periodo';

    return `${one.charAt(0).toUpperCase()}${one.slice(1)} ${period}`;
}

const MONTHS = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

/** Día y mes cortos para el sello de fecha de las tarjetas. */
export function dateBadge(value: string | null): { day: string; month: string; year: string } | null {
    if (!value) {
        return null;
    }

    const [year, month, day] = value.substring(0, 10).split('-');

    return { day: String(Number(day)), month: MONTHS[Number(month) - 1] ?? '', year };
}

export function dateRange(start: string | null, end: string | null): string {
    const format = (value: string) =>
        new Date(`${value.substring(0, 10)}T00:00:00`).toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: 'numeric' });

    if (!start) {
        return '';
    }

    return end && end !== start ? `${format(start)} al ${format(end)}` : format(start);
}
