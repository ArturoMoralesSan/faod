<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    CalendarClock,
    GraduationCap,
    History,
    Landmark,
    Mail,
    MapPin,
    Pencil,
    Phone,
    Plus,
    Trash2,
    UserRound,
} from 'lucide-vue-next';
import { ref } from 'vue';

import AdminButton from '@/components/admin/AdminButton.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import AttachmentsCard from '@/components/admin/AttachmentsCard.vue';
import DetailItem from '@/components/admin/DetailItem.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import PersonPhoto from '@/components/admin/PersonPhoto.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { confirmDelete, enumOptions, formatDate, fullName, label, recordOptions, toDateInput } from '@/lib/admin';
import { can as hasPermission } from '@/lib/permissions';
import admin from '@/routes/admin';
import type { Attachment, Option, PersonName } from '@/types/admin';

interface Registration {
    id: number;
    academic_period_id: number;
    status: string;
    registered_at: string | null;
    notes: string | null;
    academic_period: Option;
    verifier: { id: number; name: string } | null;
    attachments: Attachment[];
}

/** Sin permiso de edición la página es de solo consulta. */
const canEdit = hasPermission('students.edit');

const props = defineProps<{
    enrollment: {
        id: number;
        status: string;
        started_at: string | null;
        withdrawn_at: string | null;
        withdrawal_reason: string | null;
        graduated_at: string | null;
        notes: string | null;
        student: {
            id: number;
            registration_number: string;
            people: PersonName & {
                id: number;
                photo_path: string | null;
                curp: string | null;
                birth_date: string | null;
                street: string | null;
                exterior_number: string | null;
                neighborhood: string | null;
                city: string | null;
                state: string | null;
                postal_code: string | null;
            };
            attachments: Attachment[];
            groups: { id: number; code: string; period_number: number; academic_period: Option }[];
        };
        academic_program: Option & { specialty: Option | null };
        cohort: Option | null;
        study_plan: { id: number; name: string; version: string } | null;
        university_registrations: Registration[];
        status_changes: {
            id: number;
            from_status: string | null;
            to_status: string;
            reason: string | null;
            changed_at: string;
            user: { id: number; name: string } | null;
        }[];
        attachments: Attachment[];
    };
    universityStatuses: string[];
    periods: Option[];
    canEdit: boolean;
}>();

const person = props.enrollment.student.people;

const address = [
    [person.street, person.exterior_number].filter(Boolean).join(' '),
    person.neighborhood,
    person.city,
    person.state,
    person.postal_code,
]
    .filter(Boolean)
    .join(', ');

/* ---------------------- Inscripción universidad ---------------------- */

const dialogOpen = ref(false);
const editing = ref<Registration | null>(null);
const form = useForm({
    academic_period_id: null as number | null,
    status: 'pendiente',
    registered_at: '',
    notes: '',
});

const openRegistration = (registration: Registration | null = null): void => {
    editing.value = registration;
    form.clearErrors();
    form.academic_period_id = registration?.academic_period_id ?? props.periods[0]?.id ?? null;
    form.status = registration?.status ?? 'pendiente';
    form.registered_at = toDateInput(registration?.registered_at);
    form.notes = registration?.notes ?? '';
    dialogOpen.value = true;
};

const submit = (): void => {
    const options = { preserveScroll: true, onSuccess: () => (dialogOpen.value = false) };

    if (editing.value) {
        form
            .transform(({ academic_period_id: _ignored, ...data }) => data)
            .put(admin.universityRegistrations.update(editing.value.id).url, options);
    } else {
        form
            .transform((data) => data)
            .post(admin.students.universityRegistrations.store(props.enrollment.id).url, options);
    }
};

const destroyRegistration = (registration: Registration): void => {
    confirmDelete(
        '¿Eliminar registro?',
        `Se eliminará la situación de inscripción del periodo ${registration.academic_period.name}.`,
        admin.universityRegistrations.destroy(registration.id).url,
        { preserveState: true },
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Alumnos', href: admin.students.index() },
            { title: 'Expediente', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="fullName(person)" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            :title="fullName(person)"
            :description="`Matrícula ${enrollment.student.registration_number} · ${enrollment.academic_program.name}`"
            section="Alumnos"
            :icon="GraduationCap"
            :action-icon="Pencil"
            action-label="Editar inscripción"
            :action-href="admin.students.edit(enrollment.id).url"
            action-permission="students.edit"
        >
            <template #details>
                <div class="mt-2 flex flex-wrap gap-2">
                    <StatusBadge :value="enrollment.status" />
                </div>
            </template>
        </PageHeader>

        <div class="grid gap-5 xl:grid-cols-3">
            <div class="flex flex-col gap-5 xl:col-span-2">
                <!-- Programa -->
                <AdminCard title="Programa académico" :icon="GraduationCap">
                    <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <DetailItem label="Programa">
                            <Link :href="admin.academicPrograms.show(enrollment.academic_program.id).url" class="text-[#941B2B] hover:underline">
                                {{ enrollment.academic_program.name }}
                            </Link>
                        </DetailItem>
                        <DetailItem label="Especialidad" :value="enrollment.academic_program.specialty?.name" />
                        <DetailItem label="Generación" :value="enrollment.cohort?.name" />
                        <DetailItem
                            label="Plan académico"
                            :value="enrollment.study_plan ? `${enrollment.study_plan.name} (v${enrollment.study_plan.version})` : null"
                        />
                        <DetailItem label="Ingreso" :value="formatDate(enrollment.started_at)" />
                        <DetailItem v-if="enrollment.status === 'egresado'" label="Egreso" :value="formatDate(enrollment.graduated_at)" />
                        <DetailItem v-if="enrollment.status === 'baja'" label="Baja" :value="formatDate(enrollment.withdrawn_at)" />
                    </dl>

                    <p v-if="enrollment.withdrawal_reason" class="mt-5 rounded-xl border border-[#E8C7CC] bg-[#F9ECEE] p-4 text-sm text-[#721421]">
                        <strong>Motivo de la baja:</strong> {{ enrollment.withdrawal_reason }}
                    </p>

                    <p v-if="enrollment.notes" class="mt-5 whitespace-pre-line text-sm text-[#525960]">
                        {{ enrollment.notes }}
                    </p>
                </AdminCard>

                <!-- Inscripción universidad -->
                <AdminCard
                    title="Inscripción ante la universidad"
                    description="Situación por periodo. Adjunta el comprobante cuando corresponda."
                    :icon="Landmark"
                    :padding="false"
                >
                    <ul v-if="enrollment.university_registrations.length" class="divide-y divide-[#E3E5E8]">
                        <li v-for="registration in enrollment.university_registrations" :key="registration.id" class="px-5 py-4 sm:px-6">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-[#292D32]">{{ registration.academic_period.name }}</p>
                                    <p class="text-xs text-[#858B93]">
                                        <template v-if="registration.registered_at">Inscrito el {{ formatDate(registration.registered_at) }} · </template>
                                        <template v-if="registration.verifier">Verificó {{ registration.verifier.name }}</template>
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <StatusBadge :value="registration.status" />
                                    <button v-if="canEdit" type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-[#383D43] hover:bg-[#F1F2F4] hover:text-[#292D32]" title="Editar" @click="openRegistration(registration)">
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                    <button v-if="canEdit" type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F1F2F4]" title="Eliminar" @click="destroyRegistration(registration)">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <p v-if="registration.notes" class="mt-2 text-sm text-[#525960]">{{ registration.notes }}</p>

                            <div class="mt-3">
                                <AttachmentsCard
                                    attachable-type="university_registration"
                                    :attachable-id="registration.id"
                                    :attachments="registration.attachments"
                                    title="Comprobantes"
                                    description="Comprobante de inscripción del periodo."
                                />
                            </div>
                        </li>
                    </ul>

                    <EmptyState v-else :icon="Landmark" title="Sin registros" description="Registra la situación del alumno en el periodo actual." />

                    <template v-if="canEdit" #footer>
                        <AdminButton v-if="canEdit" :icon="Plus" @click="openRegistration()">Registrar situación</AdminButton>
                    </template>
                </AdminCard>

                <!-- Historial -->
                <AdminCard title="Historial interno" :icon="History">
                    <ol v-if="enrollment.status_changes.length" class="relative ml-2 border-l border-[#E3E5E8]">
                        <li v-for="change in enrollment.status_changes" :key="change.id" class="mb-5 ml-5 last:mb-0">
                            <span class="absolute -left-[7px] mt-1.5 h-3 w-3 rounded-full border-2 border-white bg-[#941B2B]" />
                            <p class="text-sm font-semibold text-[#292D32]">
                                {{ change.from_status ? `${label(change.from_status)} → ` : '' }}{{ label(change.to_status) }}
                            </p>
                            <p class="text-xs text-[#858B93]">
                                {{ formatDate(change.changed_at) }}<template v-if="change.user"> · {{ change.user.name }}</template>
                            </p>
                            <p v-if="change.reason" class="mt-1 text-sm text-[#525960]">{{ change.reason }}</p>
                        </li>
                    </ol>
                    <p v-else class="text-sm italic text-[#858B93]">Sin movimientos.</p>
                </AdminCard>
            </div>

            <div class="flex flex-col gap-5">
                <!-- Datos personales -->
                <AdminCard title="Datos personales" :icon="UserRound">
                    <div class="mb-5 flex justify-center">
                        <PersonPhoto :person-id="person.id" :photo-path="person.photo_path" :name="fullName(person)" :editable="canEdit" />
                    </div>
                    <dl class="flex flex-col gap-4">
                        <DetailItem label="CURP" :value="person.curp" />
                        <DetailItem label="Fecha de nacimiento" :value="formatDate(person.birth_date)" />
                        <DetailItem label="Teléfono">
                            <span class="inline-flex items-center gap-1.5"><Phone class="h-3.5 w-3.5 text-[#858B93]" /> {{ person.phone ?? '—' }}</span>
                        </DetailItem>
                        <DetailItem label="Correo">
                            <span class="inline-flex items-center gap-1.5 break-all"><Mail class="h-3.5 w-3.5 shrink-0 text-[#858B93]" /> {{ person.personal_email ?? '—' }}</span>
                        </DetailItem>
                        <DetailItem label="Domicilio">
                            <span class="inline-flex items-start gap-1.5"><MapPin class="mt-0.5 h-3.5 w-3.5 shrink-0 text-[#858B93]" /> {{ address || '—' }}</span>
                        </DetailItem>
                    </dl>
                </AdminCard>

                <!-- Grupos -->
                <AdminCard title="Grupos" :icon="CalendarClock">
                    <ul v-if="enrollment.student.groups.length" class="flex flex-col gap-3">
                        <li v-for="group in enrollment.student.groups" :key="group.id">
                            <Link :href="admin.groups.show(group.id).url" class="group block">
                                <p class="text-sm font-semibold text-[#292D32] group-hover:text-[#292D32]">Grupo {{ group.code }}</p>
                                <p class="text-xs text-[#858B93]">{{ group.academic_period.name }} · Semestre {{ group.period_number }}</p>
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="text-sm italic text-[#858B93]">Sin grupos asignados.</p>
                </AdminCard>

                <!-- Documentos -->
                <AttachmentsCard
                    attachable-type="student_profile"
                    :attachable-id="enrollment.student.id"
                    :attachments="enrollment.student.attachments"
                    title="Fotografía y documentos"
                    description="Fotografía, identificación, títulos y otros documentos del alumno."
                />
            </div>
        </div>

        <!-- Diálogo: situación ante la universidad -->
        <FormDialog
            v-model:open="dialogOpen"
            :title="editing ? `Inscripción ${editing.academic_period.name}` : 'Registrar situación'"
            :loading="form.processing"
            @submit="submit"
        >
            <FormSelect
                v-if="!editing"
                id="registration_period"
                v-model="form.academic_period_id"
                label="Periodo"
                :options="recordOptions(periods)"
                :error="form.errors.academic_period_id"
                required
            />

            <FormSelect
                id="registration_status"
                v-model="form.status"
                label="Situación"
                :options="enumOptions(universityStatuses)"
                :error="form.errors.status"
                required
            />

            <FormInput
                v-if="form.status === 'inscrito'"
                id="registration_date"
                v-model="form.registered_at"
                type="date"
                label="Fecha de inscripción"
                :error="form.errors.registered_at"
                required
            />

            <div class="md:col-span-2">
                <FormTextarea id="registration_notes" v-model="form.notes" label="Observaciones" :rows="2" :error="form.errors.notes" />
            </div>
        </FormDialog>
    </div>
</template>
