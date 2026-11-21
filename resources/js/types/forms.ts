/**
 * Datos de los formularios compartidos entre Create y Edit.
 */

import type { Option, StudentOption, TeacherOption } from '@/types/admin';

export interface DepartmentFormData {
    parent_id: number | null;
    type: string;
    code: string;
    name: string;
    description: string;
    head_people_id: number | null;
    is_active: boolean;
}

export interface CatalogFormData {
    code: string;
    name: string;
    description: string;
}

export interface SpecialtyFormData {
    code: string;
    name: string;
    description: string;
    is_active: boolean;
}

export interface ProgramObjectiveData {
    type: string;
    description: string;
}

export interface ProgramTeacherData {
    teacher_profile_id: number | null;
    role: string;
    starts_at: string;
    ends_at: string;
}

export interface AcademicProgramFormData {
    department_id: number | null;
    specialty_id: number | null;
    type: string;
    code: string;
    name: string;
    description: string;
    duration_value: number | null;
    duration_unit: string | null;
    total_periods: number | null;
    is_active: boolean;
    objectives: ProgramObjectiveData[];
    teachers: ProgramTeacherData[];
}

export interface AcademicPeriodFormData {
    code: string;
    name: string;
    starts_at: string;
    ends_at: string;
    is_current: boolean;
}

export interface CohortFormData {
    academic_program_id: number | null;
    name: string;
    starts_at: string;
    ends_at: string;
    status: string;
}

export interface SubjectFormData {
    code: string;
    name: string;
    description: string;
    credits: number | null;
    theory_hours: number | null;
    practice_hours: number | null;
    is_active: boolean;
}

export interface StudyPlanFormData {
    academic_program_id: number | null;
    name: string;
    version: string;
    valid_from: string;
    description: string;
    is_active: boolean;
}

export interface ClinicFormData {
    department_id: number | null;
    code: string;
    name: string;
    location: string;
    description: string;
    is_active: boolean;
}

export interface SpaceFormData {
    clinic_id: number | null;
    clinic_area_id: number | null;
    type: string;
    code: string;
    name: string;
    building: string;
    floor: string;
    capacity: number | null;
    is_active: boolean;
}

export interface ClinicalSessionFormData {
    clinic_id: number | null;
    space_id: number | null;
    academic_period_id: number | null;
    academic_group_id: number | null;
    subject_id: number | null;
    supervisor_id: number | null;
    date: string;
    start_time: string;
    end_time: string;
    status: string;
    topic: string;
    notes: string;
}

/** Catálogos que recibe el formulario de sesión clínica. */
export interface ClinicalSessionFormOptions {
    clinics: Option[];
    spaces: (Option & { clinic_id: number })[];
    periods: (Option & { is_current: boolean })[];
    groups: { id: number; academic_period_id: number; code: string }[];
    subjects: Option[];
    teachers: TeacherOption[];
    clinicSupervisors: { clinic_id: number; teacher_profile_id: number }[];
    statuses: string[];
}

/** Catálogos que recibe el formulario de incidencias. */
export interface ClinicIncidentFormOptions {
    clinics: Option[];
    spaces: (Option & { clinic_id: number })[];
    equipment: (Option & { clinic_id: number })[];
    sessions: { id: number; clinic_id: number; date: string; start_time: string; topic: string | null }[];
    types: string[];
    severities: string[];
    statuses: string[];
}

export interface ClinicIncidentFormData {
    clinic_id: number | null;
    space_id: number | null;
    clinic_equipment_id: number | null;
    clinical_session_id: number | null;
    type: string;
    severity: string;
    status: string;
    title: string;
    description: string;
    occurred_at: string;
    resolution: string;
}

export interface PatientFormData {
    // Persona
    curp: string;
    first_name: string;
    middle_name: string;
    paternal_surname: string;
    maternal_surname: string;
    birth_date: string;
    gender: string;
    phone: string;
    personal_email: string;
    street: string;
    exterior_number: string;
    interior_number: string;
    neighborhood: string;
    postal_code: string;
    city: string;
    state: string;
    // Expediente
    clinic_id: number | null;
    blood_type: string;
    occupation: string;
    marital_status: string;
    referred_by: string;
    status: string;
    registered_at: string;
    notes: string;
}

export interface PatientFormOptions {
    clinics: Option[];
    statuses: string[];
    bloodTypes: string[];
    maritalStatuses: string[];
}

export interface AppointmentFormData {
    patient_id: number | null;
    clinic_id: number | null;
    space_id: number | null;
    student_profile_id: number | null;
    teacher_profile_id: number | null;
    treatment_plan_id: number | null;
    date: string;
    start_time: string;
    end_time: string;
    reason: string;
    notes: string;
}

export interface AppointmentFormOptions {
    clinics: Option[];
    spaces: (Option & { clinic_id: number })[];
    students: StudentOption[];
    teachers: TeacherOption[];
    treatmentPlans: { id: number; patient_id: number; title: string }[];
    patientSearchUrl: string;
}

export interface ActivityFormData {
    type: string;
    title: string;
    description: string;
    objectives: string;
    modality: string;
    starts_at: string;
    ends_at: string;
    schedule: string;
    space_id: number | null;
    location: string;
    capacity: number | null;
    hours: number | null;
    cost: number | null;
    status: string;
    coordinator_id: number | null;
    issues_certificate: boolean;
    min_attendance: number | null;
    evaluation_score: number | null;
    evaluation_notes: string;
    results: string;
}

export interface ActivityFormOptions {
    types: string[];
    modalities: string[];
    statuses: string[];
    spaces: Option[];
    teachers: TeacherOption[];
}

export interface AcademicGroupFormData {
    cohort_id: number | null;
    academic_period_id: number | null;
    period_number: number | null;
    code: string;
    capacity: number | null;
    student_ids: number[];
}

export interface PeopleFormData {
    curp: string;
    rfc: string;
    first_name: string;
    middle_name: string;
    paternal_surname: string;
    maternal_surname: string;
    birth_date: string;
    gender: string;
    phone: string;
    personal_email: string;
    street: string;
    exterior_number: string;
    interior_number: string;
    neighborhood: string;
    postal_code: string;
    city: string;
    state: string;
    country: string;

    // Secciones opcionales: se activan con un interruptor y se envían como null si están apagadas.
    has_account: boolean;
    account: {
        email: string;
        password: string;
        password_confirmation: string;
    };

    has_student_profile: boolean;
    student_profile: {
        registration_number: string;
        status: string;
        admission_date: string;
        internal_notes: string;
    };

    has_teacher_profile: boolean;
    teacher_profile: {
        employee_number: string;
        professional_license: string;
        professional_title: string;
        years_experience: number | null;
        status: string;
        professional_biography: string;
    };

    has_staff_profile: boolean;
    staff_profile: {
        employee_number: string;
        position: string;
        department_id: number | null;
        status: string;
        hired_at: string;
        notes: string;
    };
}

export interface StudentEnrollmentFormData {
    student_profile_id: number | null;
    academic_program_id: number | null;
    cohort_id: number | null;
    study_plan_id: number | null;
    status: string;
    started_at: string;
    withdrawn_at: string;
    withdrawal_reason: string;
    graduated_at: string;
    notes: string;
    status_reason: string;
}
