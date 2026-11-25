<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BriefcaseBusiness,
    GraduationCap,
    KeyRound,
    MapPin,
    Pencil,
    Plus,
    ShieldCheck,
    UserRound,
    UserRoundCheck,
    Users,
} from 'lucide-vue-next';

import AdminCard from '@/components/admin/AdminCard.vue';
import AttachmentsCard from '@/components/admin/AttachmentsCard.vue';
import DetailItem from '@/components/admin/DetailItem.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { formatDate, fullName, label } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Attachment, Option, PersonName } from '@/types/admin';

const props = defineProps<{
    person: PersonName & {
        id: number;
        curp: string | null;
        rfc: string | null;
        birth_date: string | null;
        gender: string | null;
        street: string | null;
        exterior_number: string | null;
        interior_number: string | null;
        neighborhood: string | null;
        postal_code: string | null;
        city: string | null;
        state: string | null;
        country: string | null;
        user: { id: number; name: string; email: string; is_active: boolean; roles: Option[] } | null;
        student_profile: {
            id: number;
            registration_number: string;
            status: string;
            admission_date: string | null;
            internal_notes: string | null;
            enrollments: {
                id: number;
                status: string;
                academic_program: Option;
                cohort: Option | null;
            }[];
        } | null;
        teacher_profile: {
            id: number;
            employee_number: string | null;
            professional_license: string | null;
            professional_title: string | null;
            status: string;
            specialties: Option[];
        } | null;
        staff_profile: {
            id: number;
            employee_number: string | null;
            position: string;
            status: string;
            hired_at: string | null;
            department: string | null;
            department_record: Option | null;
        } | null;
        attachments: Attachment[];
    };
}>();

const p = props.person;

const address = [
    [p.street, p.exterior_number, p.interior_number ? `int. ${p.interior_number}` : null].filter(Boolean).join(' '),
    p.neighborhood,
    p.postal_code ? `C.P. ${p.postal_code}` : null,
    p.city,
    p.state,
]
    .filter(Boolean)
    .join(', ');

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Personas', href: admin.people.index() },
            { title: 'Detalle', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="fullName(person)" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            :title="fullName(person)"
            :description="person.curp ?? 'Sin CURP registrada'"
            section="Personas"
            :icon="Users"
            :action-icon="Pencil"
            action-label="Editar persona"
            :action-href="admin.people.edit(person.id).url"
        />

        <div class="grid gap-5 xl:grid-cols-3">
            <div class="flex flex-col gap-5 xl:col-span-2">
                <AdminCard title="Datos personales" :icon="UserRound">
                    <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <DetailItem label="CURP" :value="person.curp" />
                        <DetailItem label="RFC" :value="person.rfc" />
                        <DetailItem label="Fecha de nacimiento" :value="formatDate(person.birth_date)" />
                        <DetailItem label="Género" :value="person.gender ? label(person.gender) : null" />
                        <DetailItem label="Teléfono" :value="person.phone" />
                        <DetailItem label="Correo personal" :value="person.personal_email" />
                    </dl>

                    <div class="mt-5 flex items-start gap-2 text-sm text-[#525960]">
                        <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-[#858B93]" />
                        <span>{{ address || 'Sin domicilio registrado' }}</span>
                    </div>
                </AdminCard>

                <!-- Perfil de alumno -->
                <AdminCard v-if="person.student_profile" title="Perfil de alumno" :icon="GraduationCap" :padding="false">
                    <dl class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-3 sm:p-6">
                        <DetailItem label="Matrícula" :value="person.student_profile.registration_number" />
                        <DetailItem label="Admisión" :value="formatDate(person.student_profile.admission_date)" />
                        <DetailItem label="Estado"><StatusBadge :value="person.student_profile.status" /></DetailItem>
                    </dl>

                    <div class="border-t border-[#E3E5E8]">
                        <p class="px-5 pt-4 text-xs font-semibold uppercase tracking-wide text-[#858B93] sm:px-6">Inscripciones internas</p>

                        <ul v-if="person.student_profile.enrollments.length" class="divide-y divide-[#E3E5E8]">
                            <li v-for="enrollment in person.student_profile.enrollments" :key="enrollment.id">
                                <Link
                                    :href="admin.students.show(enrollment.id).url"
                                    class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-[#F4F5F6] sm:px-6"
                                >
                                    <span>
                                        <span class="block text-sm font-semibold text-[#292D32]">{{ enrollment.academic_program.name }}</span>
                                        <span class="text-xs text-[#858B93]">Generación {{ enrollment.cohort?.name ?? '—' }}</span>
                                    </span>
                                    <StatusBadge :value="enrollment.status" />
                                </Link>
                            </li>
                        </ul>

                        <p v-else class="px-5 py-3 text-sm italic text-[#858B93] sm:px-6">Sin inscripciones a programas.</p>
                    </div>

                    <template #footer>
                        <Link
                            :href="`${admin.students.create().url}?student_profile_id=${person.student_profile.id}`"
                            class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#941B2B] hover:underline"
                        >
                            <Plus class="h-4 w-4" /> Inscribir a un programa
                        </Link>
                    </template>
                </AdminCard>

                <!-- Perfil docente -->
                <AdminCard v-if="person.teacher_profile" title="Perfil docente" :icon="UserRoundCheck">
                    <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <DetailItem label="Título" :value="person.teacher_profile.professional_title" />
                        <DetailItem label="Cédula" :value="person.teacher_profile.professional_license" />
                        <DetailItem label="No. de empleado" :value="person.teacher_profile.employee_number" />
                        <DetailItem label="Estado"><StatusBadge :value="person.teacher_profile.status" /></DetailItem>
                    </dl>

                    <div v-if="person.teacher_profile.specialties.length" class="mt-4 flex flex-wrap gap-1.5">
                        <span
                            v-for="specialty in person.teacher_profile.specialties"
                            :key="specialty.id"
                            class="inline-flex items-center rounded-full border border-[#E8C7CC] bg-[#F9ECEE] px-2.5 py-1 text-xs font-semibold text-[#941B2B]"
                        >
                            {{ specialty.name }}
                        </span>
                    </div>

                    <template #footer>
                        <Link
                            :href="admin.teachers.show(person.teacher_profile.id).url"
                            class="text-sm font-semibold text-[#941B2B] hover:underline"
                        >
                            Ver expediente docente
                        </Link>
                    </template>
                </AdminCard>

                <!-- Perfil administrativo -->
                <AdminCard v-if="person.staff_profile" title="Perfil administrativo" :icon="BriefcaseBusiness">
                    <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <DetailItem label="Puesto" :value="person.staff_profile.position" />
                        <DetailItem
                            label="Departamento"
                            :value="person.staff_profile.department_record?.name ?? person.staff_profile.department"
                        />
                        <DetailItem label="Ingreso" :value="formatDate(person.staff_profile.hired_at)" />
                        <DetailItem label="Estado"><StatusBadge :value="person.staff_profile.status" /></DetailItem>
                    </dl>
                </AdminCard>
            </div>

            <div class="flex flex-col gap-5">
                <AdminCard title="Cuenta de acceso" :icon="KeyRound">
                    <template v-if="person.user">
                        <dl class="flex flex-col gap-4">
                            <DetailItem label="Correo" :value="person.user.email" />
                            <DetailItem label="Estado"><StatusBadge :value="person.user.is_active ?? true" /></DetailItem>
                            <DetailItem label="Roles">
                                <div class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="role in person.user.roles"
                                        :key="role.id"
                                        class="inline-flex items-center gap-1 rounded-full border border-[#E8C7CC] bg-[#F9ECEE] px-2.5 py-1 text-xs font-semibold text-[#941B2B]"
                                    >
                                        <ShieldCheck class="h-3 w-3" /> {{ label(role.name) }}
                                    </span>
                                    <span v-if="!person.user.roles.length" class="text-xs italic text-[#858B93]">Sin roles</span>
                                </div>
                            </DetailItem>
                        </dl>
                    </template>

                    <p v-else class="text-sm italic text-[#858B93]">
                        No tiene cuenta. Puedes crearla editando a la persona.
                    </p>

                    <template v-if="person.user" #footer>
                        <Link :href="admin.users.edit(person.user.id).url" class="text-sm font-semibold text-[#941B2B] hover:underline">
                            Administrar roles
                        </Link>
                    </template>
                </AdminCard>

                <AttachmentsCard
                    attachable-type="people"
                    :attachable-id="person.id"
                    :attachments="person.attachments"
                    title="Documentos personales"
                    description="Identificación, comprobante de domicilio, fotografía, etc."
                />
            </div>
        </div>
    </div>
</template>
