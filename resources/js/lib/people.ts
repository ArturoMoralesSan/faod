import { toDateInput } from '@/lib/admin';
import type { PeopleFormData } from '@/types/forms';

/**
 * Valores iniciales del formulario de personas (vacío o a partir de un registro).
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function peopleFormDefaults(person?: Record<string, any> | null): PeopleFormData {
    const p = person ?? {};
    const student = p.student_profile;
    const teacher = p.teacher_profile;
    const staff = p.staff_profile;

    return {
        curp: p.curp ?? '',
        rfc: p.rfc ?? '',
        first_name: p.first_name ?? '',
        middle_name: p.middle_name ?? '',
        paternal_surname: p.paternal_surname ?? '',
        maternal_surname: p.maternal_surname ?? '',
        birth_date: toDateInput(p.birth_date),
        gender: p.gender ?? '',
        phone: p.phone ?? '',
        personal_email: p.personal_email ?? '',
        street: p.street ?? '',
        exterior_number: p.exterior_number ?? '',
        interior_number: p.interior_number ?? '',
        neighborhood: p.neighborhood ?? '',
        postal_code: p.postal_code ?? '',
        city: p.city ?? '',
        state: p.state ?? '',
        country: p.country ?? 'México',

        has_account: Boolean(p.user),
        account: {
            email: p.user?.email ?? '',
            password: '',
            password_confirmation: '',
        },

        has_student_profile: Boolean(student),
        student_profile: {
            registration_number: student?.registration_number ?? '',
            status: student?.status ?? 'active',
            admission_date: toDateInput(student?.admission_date),
            internal_notes: student?.internal_notes ?? '',
        },

        has_teacher_profile: Boolean(teacher),
        teacher_profile: {
            employee_number: teacher?.employee_number ?? '',
            professional_license: teacher?.professional_license ?? '',
            professional_title: teacher?.professional_title ?? '',
            highest_degree: teacher?.highest_degree ?? '',
            years_experience: teacher?.years_experience ?? null,
            status: teacher?.status ?? 'active',
            professional_biography: teacher?.professional_biography ?? '',
        },

        has_staff_profile: Boolean(staff),
        staff_profile: {
            employee_number: staff?.employee_number ?? '',
            position: staff?.position ?? '',
            department_id: staff?.department_id ?? null,
            status: staff?.status ?? 'active',
            hired_at: toDateInput(staff?.hired_at),
            notes: staff?.notes ?? '',
        },
    };
}

/** Convierte '' en null para que las reglas "nullable" del backend funcionen. */
function blanksToNull<T extends Record<string, unknown>>(data: T): T {
    return Object.fromEntries(
        Object.entries(data).map(([key, value]) => [key, value === '' ? null : value]),
    ) as T;
}

/**
 * Datos que se envían al backend: las secciones apagadas viajan como null
 * (en edición, un perfil en null se elimina).
 */
export function peoplePayload(data: PeopleFormData, options: { keepAccountWhenOff?: boolean } = {}) {
    const {
        has_account,
        account,
        has_student_profile,
        student_profile,
        has_teacher_profile,
        teacher_profile,
        has_staff_profile,
        staff_profile,
        ...personal
    } = data;

    return {
        ...blanksToNull(personal),
        account: has_account
            ? account
            : options.keepAccountWhenOff
              ? undefined
              : null,
        student_profile: has_student_profile ? blanksToNull(student_profile) : null,
        teacher_profile: has_teacher_profile ? blanksToNull(teacher_profile) : null,
        staff_profile: has_staff_profile ? blanksToNull(staff_profile) : null,
    };
}
