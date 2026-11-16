/**
 * Datos de los formularios compartidos entre Create y Edit.
 */

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
    type: string;
    code: string;
    name: string;
    building: string;
    floor: string;
    capacity: number | null;
    is_active: boolean;
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
        highest_degree: string;
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
