<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Award, Info, Mic, Pencil, Plus, Presentation, Save, Trash2, UserPlus, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import AdminButton from '@/components/admin/AdminButton.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import AttachmentsCard from '@/components/admin/AttachmentsCard.vue';
import DetailItem from '@/components/admin/DetailItem.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import SearchSelect from '@/components/admin/SearchSelect.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { confirmDelete, enumOptions, formatDate, formatMoney, fullName, label } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Attachment, PersonName, SelectOption, TeacherOption } from '@/types/admin';

interface Participant {
    id: number;
    status: string;
    attendance: number | null;
    grade: number | null;
    payment_status: string;
    certificate_folio: string | null;
    notes: string | null;
    people: PersonName & {
        personal_email: string | null;
        student_profile: { registration_number: string } | null;
        teacher_profile: { id: number } | null;
    };
}

const props = defineProps<{
    activity: {
        id: number;
        type: string;
        title: string;
        description: string | null;
        modality: string;
        starts_at: string;
        ends_at: string | null;
        schedule: string | null;
        location: string | null;
        capacity: number | null;
        hours: number | null;
        cost: number | null;
        status: string;
        issues_certificate: boolean;
        min_attendance: number;
        coordinator: { people: PersonName | null } | null;
        space: { code: string; name: string } | null;
        instructors: { id: number; name: string | null; role: string; teacher: { people: PersonName | null } | null }[];
        participants: Participant[];
        attachments: Attachment[];
    };
    participantStatuses: string[];
    paymentStatuses: string[];
    teachers: TeacherOption[];
    peopleSearchUrl: string;
}>();

const teacherOptions = computed<SelectOption[]>(() => props.teachers.map((t) => ({ value: t.id, label: fullName(t.people) })));
const active = computed(() => props.activity.participants.filter((p) => p.status !== 'baja').length);

const kind = (p: Participant) =>
    p.people.student_profile ? `Alumno ${p.people.student_profile.registration_number}` : p.people.teacher_profile ? 'Docente' : 'Externo';

/* ------------------------------- Participantes ------------------------------- */

const form = useForm({
    participants: [...props.activity.participants]
        .sort((a, b) => fullName(a.people).localeCompare(fullName(b.people)))
        .map((p) => ({ id: p.id, status: p.status, attendance: p.attendance, grade: p.grade, payment_status: p.payment_status, notes: p.notes ?? '' })),
});

const byId = (id: number) => props.activity.participants.find((p) => p.id === id)!;

const save = (): void => {
    form.transform((data) => ({
        participants: data.participants.map((p) => ({
            ...p,
            attendance: p.attendance === null || String(p.attendance) === '' ? null : p.attendance,
            grade: p.grade === null || String(p.grade) === '' ? null : p.grade,
            notes: p.notes || null,
        })),
    })).put(admin.activities.participants.update(props.activity.id).url, { preserveScroll: true });
};

const markAll = (status: string): void => form.participants.forEach((p) => p.status !== 'baja' && (p.status = status));

const issue = (): void => {
    router.post(admin.activities.certificates.issue(props.activity.id).url, {}, { preserveScroll: true });
};

const remove = (id: number): void => {
    confirmDelete('¿Quitar participante?', 'Se eliminará su inscripción.', admin.activities.participants.destroy({ activity: props.activity.id, participant: id }).url, { preserveState: false });
};

/* --------------------------------- Inscribir --------------------------------- */

const enrollOpen = ref(false);
const enrollMode = ref<'registered' | 'external'>('registered');
const enrollForm = useForm({
    people_id: null as number | null,
    first_name: '',
    paternal_surname: '',
    maternal_surname: '',
    personal_email: '',
    phone: '',
});

const openEnroll = (): void => {
    enrollForm.reset();
    enrollForm.clearErrors();
    enrollMode.value = 'registered';
    enrollOpen.value = true;
};

const submitEnroll = (): void => {
    enrollForm.transform((data) => (enrollMode.value === 'registered'
        ? { people_id: data.people_id }
        : { ...data, people_id: null, maternal_surname: data.maternal_surname || null, personal_email: data.personal_email || null, phone: data.phone || null }))
        .post(admin.activities.participants.store(props.activity.id).url, { preserveScroll: true, onSuccess: () => (enrollOpen.value = false) });
};

/* --------------------------------- Ponentes --------------------------------- */

const instructorOpen = ref(false);
const instructorForm = useForm({ teacher_profile_id: null as number | null, name: '', role: 'ponente' });

const submitInstructor = (): void => {
    instructorForm.transform((data) => ({ ...data, name: data.name || null }))
        .post(admin.activities.instructors.store(props.activity.id).url, { preserveScroll: true, onSuccess: () => { instructorOpen.value = false; instructorForm.reset(); } });
};

const removeInstructor = (id: number): void => {
    confirmDelete('¿Quitar ponente?', '', admin.activities.instructors.destroy({ activity: props.activity.id, instructor: id }).url, { preserveState: true });
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Cursos y actividades', href: admin.activities.index() },
            { title: 'Detalle', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="activity.title" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            :title="activity.title"
            :description="`${label(activity.type)} · ${label(activity.modality)}`"
            section="Cursos y actividades"
            :icon="Presentation"
            :action-icon="Pencil"
            action-label="Editar actividad"
            :action-href="admin.activities.edit(activity.id).url"
        >
            <template #details>
                <div class="mt-2"><StatusBadge :value="activity.status" /></div>
            </template>
        </PageHeader>

        <div class="grid gap-5 xl:grid-cols-3">
            <AdminCard title="Información" :icon="Info" class="xl:col-span-2">
                <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <DetailItem label="Fechas" :value="`${formatDate(activity.starts_at)}${activity.ends_at ? ` – ${formatDate(activity.ends_at)}` : ''}`" />
                    <DetailItem label="Horario" :value="activity.schedule" />
                    <DetailItem label="Lugar" :value="activity.space?.name ?? activity.location" />
                    <DetailItem label="Coordinador" :value="activity.coordinator ? fullName(activity.coordinator.people) : null" />
                    <DetailItem label="Horas" :value="activity.hours" />
                    <DetailItem label="Cupo" :value="activity.capacity ? `${active} / ${activity.capacity}` : `${active} (sin límite)`" />
                    <DetailItem label="Costo" :value="activity.cost ? formatMoney(activity.cost) : 'Sin costo'" />
                    <DetailItem label="Constancia" :value="activity.issues_certificate ? `Sí, con ${activity.min_attendance}% de asistencia` : 'No'" />
                </dl>
                <p v-if="activity.description" class="mt-5 whitespace-pre-line text-sm leading-6 text-[#525960]">{{ activity.description }}</p>
            </AdminCard>

            <AdminCard title="Ponentes" :icon="Mic">
                <ul v-if="activity.instructors.length" class="flex flex-col gap-2">
                    <li v-for="i in activity.instructors" :key="i.id" class="flex items-center justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold text-[#292D32]">{{ i.teacher ? fullName(i.teacher.people) : i.name }}</p>
                            <p class="text-xs text-[#858B93]">{{ i.role }}{{ i.teacher ? ' · docente de la facultad' : ' · externo' }}</p>
                        </div>
                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F9ECEE]" title="Quitar" @click="removeInstructor(i.id)">
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </li>
                </ul>
                <p v-else class="text-sm italic text-[#858B93]">Sin ponentes registrados.</p>
                <template #footer>
                    <button type="button" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#941B2B] hover:underline" @click="instructorOpen = true">
                        <Plus class="h-4 w-4" /> Agregar ponente
                    </button>
                </template>
            </AdminCard>
        </div>

        <AdminCard title="Participantes" :description="`${active} inscrito(s)`" :icon="Users" :padding="false">
            <div class="flex flex-wrap items-center gap-2 border-b border-[#E3E5E8] px-5 py-3 sm:px-6">
                <AdminButton :icon="UserPlus" @click="openEnroll">Inscribir</AdminButton>
                <button v-if="form.participants.length" type="button" class="text-xs font-semibold text-[#941B2B] hover:underline" @click="markAll('acreditado')">Marcar todos como acreditados</button>
                <span class="flex-1" />
                <AdminButton v-if="activity.issues_certificate && form.participants.length" variant="secondary" :icon="Award" @click="issue">Emitir constancias</AdminButton>
            </div>

            <div v-if="form.participants.length" class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#F7F8FA] text-xs font-semibold uppercase tracking-wide text-[#383D43]">
                        <tr>
                            <th class="px-4 py-3">Participante</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Asistencia %</th>
                            <th class="px-4 py-3">Calif.</th>
                            <th class="px-4 py-3">Pago</th>
                            <th class="px-4 py-3">Constancia</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E3E5E8]">
                        <tr v-for="row in form.participants" :key="row.id">
                            <td class="px-4 py-2">
                                <p class="font-semibold text-[#292D32]">{{ fullName(byId(row.id).people) }}</p>
                                <p class="text-xs text-[#858B93]">{{ kind(byId(row.id)) }}<template v-if="byId(row.id).people.personal_email"> · {{ byId(row.id).people.personal_email }}</template></p>
                            </td>
                            <td class="px-4 py-2">
                                <select v-model="row.status" class="form-select h-9 min-w-36 py-1 text-sm">
                                    <option v-for="s in participantStatuses" :key="s" :value="s">{{ label(s) }}</option>
                                </select>
                            </td>
                            <td class="px-4 py-2">
                                <input v-model="row.attendance" type="number" min="0" max="100" :class="['form-input h-9 w-20 py-1 text-sm', row.attendance !== null && String(row.attendance) !== '' && Number(row.attendance) < activity.min_attendance ? 'text-[#941B2B]' : '']" />
                            </td>
                            <td class="px-4 py-2"><input v-model="row.grade" type="number" min="0" max="100" step="0.1" class="form-input h-9 w-20 py-1 text-sm" /></td>
                            <td class="px-4 py-2">
                                <select v-model="row.payment_status" class="form-select h-9 min-w-32 py-1 text-sm">
                                    <option v-for="s in paymentStatuses" :key="s" :value="s">{{ label(s) }}</option>
                                </select>
                            </td>
                            <td class="px-4 py-2">
                                <Link
                                    v-if="byId(row.id).certificate_folio"
                                    :href="admin.activities.participants.certificate({ activity: activity.id, participant: row.id }).url"
                                    class="text-xs font-semibold text-[#941B2B] hover:underline"
                                >
                                    {{ byId(row.id).certificate_folio }}
                                </Link>
                                <span v-else class="text-xs text-[#858B93]">—</span>
                            </td>
                            <td class="px-4 py-2 text-right">
                                <button v-if="!byId(row.id).certificate_folio" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F9ECEE]" title="Quitar" @click="remove(row.id)">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="px-5 py-4 text-sm italic text-[#858B93]">Sin participantes inscritos.</p>

            <template v-if="form.participants.length" #footer>
                <AdminButton :icon="Save" :loading="form.processing" loading-text="Guardando..." @click="save">Guardar cambios</AdminButton>
            </template>
        </AdminCard>

        <AttachmentsCard
            attachable-type="academic_activity"
            :attachable-id="activity.id"
            :attachments="activity.attachments"
            title="Materiales y evidencias"
            description="Programa, presentaciones, listas de asistencia firmadas, fotografías."
        />

        <FormDialog v-model:open="enrollOpen" title="Inscribir participante" submit-label="Inscribir" :loading="enrollForm.processing" @submit="submitEnroll">
            <div class="md:col-span-2 inline-flex w-fit rounded-xl border border-[#E3E5E8] p-1 text-xs font-semibold">
                <button type="button" :class="['rounded-lg px-3 py-1.5', enrollMode === 'registered' ? 'bg-[#941B2B] text-white' : 'text-[#525960]']" @click="enrollMode = 'registered'">Persona registrada</button>
                <button type="button" :class="['rounded-lg px-3 py-1.5', enrollMode === 'external' ? 'bg-[#941B2B] text-white' : 'text-[#525960]']" @click="enrollMode = 'external'">Externo (nuevo)</button>
            </div>

            <div v-if="enrollMode === 'registered'" class="md:col-span-2">
                <SearchSelect id="en_people" v-model="enrollForm.people_id" label="Persona" :url="peopleSearchUrl" placeholder="Alumno, docente o persona registrada..." :error="enrollForm.errors.people_id" required />
            </div>
            <template v-else>
                <FormInput id="en_first" v-model="enrollForm.first_name" label="Nombre(s)" :error="enrollForm.errors.first_name" required />
                <FormInput id="en_pat" v-model="enrollForm.paternal_surname" label="Apellido paterno" :error="enrollForm.errors.paternal_surname" required />
                <FormInput id="en_mat" v-model="enrollForm.maternal_surname" label="Apellido materno" />
                <FormInput id="en_phone" v-model="enrollForm.phone" type="tel" label="Teléfono" :error="enrollForm.errors.phone" />
                <div class="md:col-span-2">
                    <FormInput id="en_email" v-model="enrollForm.personal_email" type="email" label="Correo" :error="enrollForm.errors.personal_email" />
                </div>
                <p v-if="enrollForm.errors.people_id" class="form-error md:col-span-2">{{ enrollForm.errors.people_id }}</p>
            </template>
        </FormDialog>

        <FormDialog v-model:open="instructorOpen" title="Agregar ponente" :loading="instructorForm.processing" @submit="submitInstructor">
            <div class="md:col-span-2">
                <FormSelect id="in_teacher" v-model="instructorForm.teacher_profile_id" label="Docente de la facultad" placeholder="Es externo" :options="teacherOptions" :error="instructorForm.errors.teacher_profile_id" nullable />
            </div>
            <div v-if="!instructorForm.teacher_profile_id" class="md:col-span-2">
                <FormInput id="in_name" v-model="instructorForm.name" label="Nombre del ponente externo" :error="instructorForm.errors.name" required />
            </div>
            <FormSelect id="in_role" v-model="instructorForm.role" label="Función" :options="enumOptions(['ponente', 'instructor', 'moderador', 'conferencista'])" required />
        </FormDialog>
    </div>
</template>
