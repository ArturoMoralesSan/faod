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

export interface OdontogramEntryData {
    id: number;
    tooth: string;
    surface: string | null;
    condition: string;
    notes: string | null;
    recorded_at: string;
    patient_diagnosis_id: number | null;
    creator: { name: string } | null;
}

export interface ReviewData {
    id: number;
    decision: string;
    comments: string | null;
    created_at: string;
    reviewer: { name: string } | null;
}

export interface InventoryItemData {
    id: number;
    code: string;
    name: string;
    category: string | null;
    unit: string;
    minimum_stock: number;
    tracks_expiration: boolean;
    default_supplier_id: number | null;
    description: string | null;
    is_active: boolean;
}

export interface MovementRow {
    id: number;
    type: string;
    quantity: number;
    balance_after: number;
    unit_cost: number | null;
    reason: string | null;
    reference: string | null;
    moved_at: string;
    item?: { id: number; code: string; name: string; unit: string };
    warehouse: { code: string; name: string };
    lot: { lot_number: string | null; expires_at: string | null } | null;
    user: { name: string } | null;
    supplier: { name: string } | null;
}

/** Alumno o paciente al que se le cobra. */
export interface PayerInfo {
    type: 'student' | 'patient';
    id: number;
    name: string | null;
    code: string;
    url: string;
}

export interface ChargeData {
    id: number;
    description: string;
    amount: number;
    discount_amount: number;
    discount_reason: string | null;
    paid_amount: number;
    balance: number;
    due_date: string | null;
    status: string;
    cancellation_reason: string | null;
    concept: { id: number; code: string; name: string } | null;
    academic_period: Option | null;
    payer_info?: PayerInfo | null;
}

/** Resultado de un SearchSelect (búsqueda en el servidor). */
export interface SearchResult {
    id: number;
    label: string;
    hint?: string | null;
    [key: string]: unknown;
}

export interface SelectOption {
    value: string | number;
    label: string;
}
