<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import {
    BriefcaseBusiness,
    Fingerprint,
    GraduationCap,
    Hash,
    KeyRound,
    LockKeyhole,
    Mail,
    MapPin,
    Phone,
    UserRound,
    UserRoundCheck,
} from 'lucide-vue-next';

import AdminCard from '@/components/admin/AdminCard.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import { recordOptions } from '@/lib/admin';
import type { Option } from '@/types/admin';
import type { PeopleFormData } from '@/types/forms';

const props = defineProps<{
    form: InertiaForm<PeopleFormData>;
    departments: Option[];
    /** En edición: la persona ya tiene cuenta (no se puede quitar desde aquí). */
    hasExistingAccount?: boolean;
    isEdit?: boolean;
}>();

const errorFor = (key: string): string =>
    (props.form.errors as Record<string, string>)[key] ?? '';

const genderOptions = [
    { value: 'femenino', label: 'Femenino' },
    { value: 'masculino', label: 'Masculino' },
    { value: 'no_binario', label: 'No binario' },
    { value: 'prefiero_no_decir', label: 'Prefiero no decir' },
];

const studentStatuses = [
    { value: 'active', label: 'Activo' },
    { value: 'inactive', label: 'Inactivo' },
    { value: 'suspended', label: 'Suspendido' },
    { value: 'graduated', label: 'Egresado' },
];

const basicStatuses = [
    { value: 'active', label: 'Activo' },
    { value: 'inactive', label: 'Inactivo' },
];

const profileToggleClass = (active: boolean) => [
    'flex cursor-pointer items-center justify-between gap-4 rounded-xl border p-4 transition-[border-color,background-color] duration-200',
    active ? 'border-[#E8C7CC] bg-[#FDF8F8]' : 'border-[#E3E5E8] bg-white hover:border-[#E8C7CC] hover:bg-[#FAF7F7]',
];
</script>

<template>
    <!-- Datos personales -->
    <AdminCard
        title="Datos personales"
        description="La persona se registra una sola vez aunque sea alumno, docente y administrativo a la vez."
        :icon="UserRound"
    >
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
            <FormInput id="first_name" v-model="form.first_name" label="Nombre(s)" :error="form.errors.first_name" required />
            <FormInput id="middle_name" v-model="form.middle_name" label="Segundo nombre" :error="form.errors.middle_name" />
            <FormInput id="paternal_surname" v-model="form.paternal_surname" label="Apellido paterno" :error="form.errors.paternal_surname" required />
            <FormInput id="maternal_surname" v-model="form.maternal_surname" label="Apellido materno" :error="form.errors.maternal_surname" />

            <FormInput id="curp" v-model="form.curp" label="CURP" :icon="Fingerprint" :maxlength="18" :error="form.errors.curp" />
            <FormInput id="rfc" v-model="form.rfc" label="RFC" :icon="Hash" :maxlength="13" :error="form.errors.rfc" />
            <FormInput id="birth_date" v-model="form.birth_date" type="date" label="Fecha de nacimiento" :error="form.errors.birth_date" />
            <FormSelect id="gender" v-model="form.gender" label="Género" placeholder="Sin especificar" :options="genderOptions" :error="form.errors.gender" />
        </div>
    </AdminCard>

    <!-- Contacto y domicilio -->
    <AdminCard title="Contacto y domicilio" :icon="MapPin">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
            <FormInput id="phone" v-model="form.phone" type="tel" label="Teléfono" :icon="Phone" :error="form.errors.phone" />
            <div class="xl:col-span-3">
                <FormInput id="personal_email" v-model="form.personal_email" type="email" label="Correo personal" :icon="Mail" :error="form.errors.personal_email" />
            </div>

            <div class="md:col-span-2">
                <FormInput id="street" v-model="form.street" label="Calle" :error="form.errors.street" />
            </div>
            <FormInput id="exterior_number" v-model="form.exterior_number" label="No. exterior" :error="form.errors.exterior_number" />
            <FormInput id="interior_number" v-model="form.interior_number" label="No. interior" :error="form.errors.interior_number" />

            <FormInput id="neighborhood" v-model="form.neighborhood" label="Colonia" :error="form.errors.neighborhood" />
            <FormInput id="postal_code" v-model="form.postal_code" label="Código postal" :maxlength="10" :error="form.errors.postal_code" />
            <FormInput id="city" v-model="form.city" label="Ciudad" :error="form.errors.city" />
            <FormInput id="state" v-model="form.state" label="Estado" :error="form.errors.state" />
        </div>
    </AdminCard>

    <!-- Cuenta de acceso -->
    <AdminCard
        title="Cuenta de acceso"
        description="Opcional. Sin cuenta, la persona existe en el sistema pero no puede iniciar sesión."
        :icon="KeyRound"
    >
        <label :class="profileToggleClass(form.has_account)">
            <span>
                <span class="block text-sm font-semibold text-[#292D32]">
                    {{ hasExistingAccount ? 'Tiene cuenta de acceso' : 'Crear cuenta de acceso' }}
                </span>
                <span class="mt-1 block text-xs text-[#737982]">
                    Al activar perfiles se le asignan los roles correspondientes (alumno, docente, personal administrativo).
                </span>
            </span>
            <input v-model="form.has_account" type="checkbox" class="form-checkbox shrink-0" :disabled="hasExistingAccount" />
        </label>

        <div v-if="form.has_account" class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-3">
            <FormInput
                id="account_email"
                v-model="form.account.email"
                type="email"
                label="Correo de acceso"
                :icon="Mail"
                autocomplete="email"
                :error="errorFor('account.email')"
                required
            />
            <FormInput
                id="account_password"
                v-model="form.account.password"
                type="password"
                :label="hasExistingAccount ? 'Nueva contraseña' : 'Contraseña'"
                :icon="LockKeyhole"
                autocomplete="new-password"
                :help="hasExistingAccount ? 'Vacío para conservar la actual.' : 'Mínimo 8 caracteres.'"
                :error="errorFor('account.password')"
                :required="!hasExistingAccount"
            />
            <FormInput
                id="account_password_confirmation"
                v-model="form.account.password_confirmation"
                type="password"
                label="Confirmar contraseña"
                :icon="LockKeyhole"
                autocomplete="new-password"
                :required="!hasExistingAccount"
            />
        </div>
    </AdminCard>

    <!-- Perfil de alumno -->
    <AdminCard title="Perfil de alumno" :icon="GraduationCap">
        <label :class="profileToggleClass(form.has_student_profile)">
            <span>
                <span class="block text-sm font-semibold text-[#292D32]">Es alumno</span>
                <span class="mt-1 block text-xs text-[#737982]">
                    Después podrás inscribirlo a un programa desde Alumnos.
                    <template v-if="isEdit"> Si lo desactivas, se elimina el perfil de alumno.</template>
                </span>
            </span>
            <input v-model="form.has_student_profile" type="checkbox" class="form-checkbox shrink-0" />
        </label>

        <div v-if="form.has_student_profile" class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-3">
            <FormInput
                id="registration_number"
                v-model="form.student_profile.registration_number"
                label="Matrícula universitaria"
                :icon="Hash"
                :error="errorFor('student_profile.registration_number')"
                required
            />
            <FormSelect
                id="student_status"
                v-model="form.student_profile.status"
                label="Estado"
                :options="studentStatuses"
                :error="errorFor('student_profile.status')"
            />
            <FormInput
                id="admission_date"
                v-model="form.student_profile.admission_date"
                type="date"
                label="Fecha de admisión"
                :error="errorFor('student_profile.admission_date')"
            />
            <div class="md:col-span-3">
                <FormTextarea
                    id="internal_notes"
                    v-model="form.student_profile.internal_notes"
                    label="Notas internas"
                    :rows="2"
                    :error="errorFor('student_profile.internal_notes')"
                />
            </div>
        </div>
    </AdminCard>

    <!-- Perfil docente -->
    <AdminCard title="Perfil docente" :icon="UserRoundCheck">
        <label :class="profileToggleClass(form.has_teacher_profile)">
            <span>
                <span class="block text-sm font-semibold text-[#292D32]">Es docente o especialista</span>
                <span class="mt-1 block text-xs text-[#737982]">
                    Grados, certificaciones y disponibilidad se registran en su expediente docente.
                    <template v-if="isEdit"> Si lo desactivas, se elimina el perfil docente.</template>
                </span>
            </span>
            <input v-model="form.has_teacher_profile" type="checkbox" class="form-checkbox shrink-0" />
        </label>

        <div v-if="form.has_teacher_profile" class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-3">
            <FormInput
                id="teacher_employee_number"
                v-model="form.teacher_profile.employee_number"
                label="No. de empleado"
                :icon="Hash"
                :error="errorFor('teacher_profile.employee_number')"
            />
            <FormInput
                id="professional_license"
                v-model="form.teacher_profile.professional_license"
                label="Cédula profesional"
                :error="errorFor('teacher_profile.professional_license')"
            />
            <FormSelect
                id="teacher_status"
                v-model="form.teacher_profile.status"
                label="Estado"
                :options="basicStatuses"
                :error="errorFor('teacher_profile.status')"
            />
            <FormInput
                id="professional_title"
                v-model="form.teacher_profile.professional_title"
                label="Título profesional"
                placeholder="Ej. Cirujano Dentista"
                :error="errorFor('teacher_profile.professional_title')"
            />
            <FormInput
                id="years_experience"
                v-model="form.teacher_profile.years_experience"
                type="number"
                min="0"
                label="Años de experiencia"
                :error="errorFor('teacher_profile.years_experience')"
            />
            <div class="md:col-span-3">
                <FormTextarea
                    id="professional_biography"
                    v-model="form.teacher_profile.professional_biography"
                    label="Semblanza profesional"
                    :rows="3"
                    :error="errorFor('teacher_profile.professional_biography')"
                />
            </div>
        </div>
    </AdminCard>

    <!-- Perfil administrativo -->
    <AdminCard title="Perfil administrativo" :icon="BriefcaseBusiness">
        <label :class="profileToggleClass(form.has_staff_profile)">
            <span>
                <span class="block text-sm font-semibold text-[#292D32]">Es personal administrativo</span>
                <span class="mt-1 block text-xs text-[#737982]">
                    Puesto y departamento dentro de la facultad.
                    <template v-if="isEdit"> Si lo desactivas, se elimina el perfil administrativo.</template>
                </span>
            </span>
            <input v-model="form.has_staff_profile" type="checkbox" class="form-checkbox shrink-0" />
        </label>

        <div v-if="form.has_staff_profile" class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-3">
            <FormInput
                id="position"
                v-model="form.staff_profile.position"
                label="Puesto"
                :icon="BriefcaseBusiness"
                :error="errorFor('staff_profile.position')"
                required
            />
            <FormSelect
                id="department_id"
                v-model="form.staff_profile.department_id"
                label="Departamento"
                placeholder="Sin departamento"
                :options="recordOptions(departments)"
                :error="errorFor('staff_profile.department_id')"
                nullable
            />
            <FormInput
                id="staff_employee_number"
                v-model="form.staff_profile.employee_number"
                label="No. de empleado"
                :icon="Hash"
                :error="errorFor('staff_profile.employee_number')"
            />
            <FormSelect
                id="staff_status"
                v-model="form.staff_profile.status"
                label="Estado"
                :options="basicStatuses"
                :error="errorFor('staff_profile.status')"
            />
            <FormInput
                id="hired_at"
                v-model="form.staff_profile.hired_at"
                type="date"
                label="Fecha de ingreso"
                :error="errorFor('staff_profile.hired_at')"
            />
            <div class="md:col-span-3">
                <FormTextarea
                    id="staff_notes"
                    v-model="form.staff_profile.notes"
                    label="Notas"
                    :rows="2"
                    :error="errorFor('staff_profile.notes')"
                />
            </div>
        </div>
    </AdminCard>
</template>
