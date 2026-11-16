export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
}

export interface Option {
    id: number;
    name: string;
    code?: string;
}

export interface PersonName {
    id: number;
    first_name: string;
    middle_name?: string | null;
    paternal_surname: string;
    maternal_surname?: string | null;
    phone?: string | null;
    personal_email?: string | null;
}

export interface TeacherOption {
    id: number;
    people_id: number;
    people: PersonName | null;
}

export interface StudentOption {
    id: number;
    people_id: number;
    registration_number: string;
    people: PersonName | null;
}

export interface Attachment {
    id: number;
    title: string;
    original_name: string;
    mime_type: string | null;
    size: number;
    created_at: string;
}

export type Days = Record<number, string>;

export interface ScheduleBlock {
    id: number;
    day_of_week: number;
    start_time: string;
    end_time: string;
    title: string;
    subtitle?: string;
    meta?: string;
    /** Índice para asignar color (p. ej. la materia). */
    colorKey?: number;
}

export interface SelectOption {
    value: string | number;
    label: string;
}
