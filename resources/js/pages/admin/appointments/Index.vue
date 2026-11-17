<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Bell,
    CalendarCheck,
    CalendarRange,
    CalendarX,
    Check,
    ChevronLeft,
    ChevronRight,
    Contact,
    List,
    Pencil,
    Phone,
    Plus,
    RotateCcw,
    Trash2,
    UserX,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

import AdminButton from '@/components/admin/AdminButton.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import DataTable from '@/components/admin/DataTable.vue';
import DetailItem from '@/components/admin/DetailItem.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { filterValue } from '@/composables/useIndexFilters';
import {
    confirmDelete,
    enumOptions,
    formatDate,
    formatDateTime,
    formatTime,
    fullName,
    label,
    recordOptions,
    todayInput,
    toDateInput,
} from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option, Paginated, PersonName, StudentOption } from '@/types/admin';

interface Appointment {
    id: number;
    patient_id: number;
    clinic_id: number;
    space_id: number | null;
    student_profile_id: number | null;
    teacher_profile_id: number | null;
    treatment_plan_id: number | null;
    date: string;
    start_time: string;
    end_time: string;
    reason: string | null;
    status: string;
    confirmed_at: string | null;
    cancellation_reason: string | null;
    reminder_sent_at: string | null;
    notes: string | null;
    patient: { id: number; record_number: string; people: PersonName & { personal_email: string | null } };
    clinic: Option;
    space: { id: number; code: string; name: string } | null;
    student: { people: PersonName | null } | null;
    teacher: { people: PersonName | null } | null;
}

const props = defineProps<{
    view: 'week' | 'list';
    appointments: Appointment[] | Paginated<Appointment>;
    weekStart: string;
    filters: { clinic_id?: string; space_id?: string; student_profile_id?: string; status?: string; search?: string; date_from?: string; date_to?: string };
    statuses: string[];
    clinics: Option[];
    spaces: (Option & { clinic_id: number })[];
    students: StudentOption[];
    can: { create: boolean; edit: boolean; delete: boolean };
}>();

/* ------------------------------ Filtros y vista ------------------------------ */

const filters = ref({
    clinic_id: filterValue(props.filters.clinic_id),
    space_id: filterValue(props.filters.space_id),
    student_profile_id: filterValue(props.filters.student_profile_id),
    status: filterValue(props.filters.status),
    search: filterValue(props.filters.search),
    date_from: filterValue(props.filters.date_from),
    date_to: filterValue(props.filters.date_to),
});

const visit = (extra: Record<string, string | undefined> = {}): void => {
    const params = Object.fromEntries(
        Object.entries({ ...filters.value, view: props.view, date: props.weekStart, ...extra }).filter(([, v]) => v),
    );

    router.get(admin.appointments.index().url, params, { preserveState: true, preserveScroll: true, replace: true });
};

const shiftWeek = (days: number): void => {
    const date = new Date(`${props.weekStart}T00:00:00`);
    date.setDate(date.getDate() + days);
    visit({ date: toDateInput(date.toISOString()) });
};

const spaceOptions = computed(() =>
    recordOptions(props.spaces.filter((s) => !filters.value.clinic_id || String(s.clinic_id) === filters.value.clinic_id), true),
);
const studentOptions = computed(() =>
    props.students.map((s) => ({ value: s.id, label: `${s.registration_number} · ${fullName(s.people)}` })),
);

const weekList = computed(() => (Array.isArray(props.appointments) ? props.appointments : []));
const paginated = computed(() => (Array.isArray(props.appointments) ? null : props.appointments));

const days = computed(() =>
    Array.from({ length: 7 }, (_, i) => {
        const date = new Date(`${props.weekStart}T00:00:00`);
        date.setDate(date.getDate() + i);
        const iso = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

        return {
            iso,
            name: date.toLocaleDateString('es-MX', { weekday: 'short' }),
            day: date.getDate(),
            items: weekList.value.filter((a) => a.date.substring(0, 10) === iso),
        };
    }).filter((d, i) => i < 6 || d.items.length), // el domingo solo si tiene citas
);

const weekLabel = computed(() => `${formatDate(days.value[0]?.iso)} – ${formatDate(days.value[days.value.length - 1]?.iso)}`);
const today = todayInput();

const BORDERS: Record<string, string> = {
    programada: 'border-l-[#D9A21B]',
    confirmada: 'border-l-[#187744]',
    atendida: 'border-l-[#2563EB]',
    no_asistio: 'border-l-[#941B2B]',
    cancelada: 'border-l-[#B5BAC1]',
    reprogramada: 'border-l-[#B5BAC1]',
};

/* --------------------------------- Detalle --------------------------------- */

const detailOpen = ref(false);
const selected = ref<Appointment | null>(null);

const openDetail = (appointment: Appointment): void => {
    selected.value = appointment;
    detailOpen.value = true;
};

const isOpen = (a: Appointment) => ['programada', 'confirmada'].includes(a.status);

const act = (action: 'confirm' | 'attend' | 'noShow' | 'remind'): void => {
    if (!selected.value) {
        return;
    }

    const id = selected.value.id;
    const options = { preserveScroll: true, onSuccess: () => (detailOpen.value = false) };

    if (action === 'confirm') router.patch(admin.appointments.confirm(id).url, {}, options);
    if (action === 'attend') router.patch(admin.appointments.attend(id).url, {}, options);
    if (action === 'noShow') router.patch(admin.appointments.noShow(id).url, {}, options);
    if (action === 'remind') router.post(admin.appointments.remind(id).url, {}, options);
};

const destroy = (): void => {
    if (selected.value) {
        detailOpen.value = false;
        confirmDelete('¿Eliminar cita?', 'Se eliminará de la agenda. Para conservar el historial, mejor cancélala.', admin.appointments.destroy(selected.value.id).url, { preserveState: true });
    }
};

/* --------------------------------- Cancelar --------------------------------- */

const cancelOpen = ref(false);
const cancelForm = useForm({ cancellation_reason: '' });

const openCancel = (): void => {
    cancelForm.reset();
    cancelForm.clearErrors();
    detailOpen.value = false;
    cancelOpen.value = true;
};

const submitCancel = (): void => {
    if (selected.value) {
        cancelForm.patch(admin.appointments.cancel(selected.value.id).url, { preserveScroll: true, onSuccess: () => (cancelOpen.value = false) });
    }
};

/* ------------------------------- Reprogramar ------------------------------- */

const rescheduleOpen = ref(false);
const rescheduleForm = useForm({
    patient_id: null as number | null,
    clinic_id: null as number | null,
    space_id: null as number | null,
    student_profile_id: null as number | null,
    teacher_profile_id: null as number | null,
    treatment_plan_id: null as number | null,
    date: '',
    start_time: '',
    end_time: '',
    reason: '' as string | null,
    notes: '' as string | null,
});

const rescheduleSpaces = computed(() =>
    recordOptions(props.spaces.filter((s) => s.clinic_id === rescheduleForm.clinic_id), true),
);

const openReschedule = (): void => {
    const a = selected.value;

    if (!a) {
        return;
    }

    rescheduleForm.clearErrors();
    Object.assign(rescheduleForm, {
        patient_id: a.patient_id,
        clinic_id: a.clinic_id,
        space_id: a.space_id,
        student_profile_id: a.student_profile_id,
        teacher_profile_id: a.teacher_profile_id,
        treatment_plan_id: a.treatment_plan_id,
        date: '',
        start_time: formatTime(a.start_time),
        end_time: formatTime(a.end_time),
        reason: a.reason,
        notes: a.notes,
    });
    detailOpen.value = false;
    rescheduleOpen.value = true;
};

const submitReschedule = (): void => {
    if (selected.value) {
        rescheduleForm.post(admin.appointments.reschedule(selected.value.id).url, { preserveScroll: true, onSuccess: () => (rescheduleOpen.value = false) });
    }
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Agenda', href: admin.appointments.index() },
        ],
    },
});
</script>

<template>
    <Head title="Agenda" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Agenda de citas"
            description="Citas de pacientes por clínica y consultorio."
            section="Agenda"
            :icon="CalendarRange"
            :action-icon="Plus"
            :action-label="can.create ? 'Nueva cita' : ''"
            :action-href="can.create ? admin.appointments.create({ query: { clinic_id: filters.clinic_id || undefined } }).url : ''"
        />

        <!-- Barra de herramientas -->
        <div class="flex flex-col gap-3 rounded-2xl border border-[#E3E5E8] bg-white p-4">
            <div class="flex flex-wrap items-center gap-3">
                <div class="inline-flex rounded-xl border border-[#E3E5E8] p-1 text-xs font-semibold">
                    <button type="button" :class="['inline-flex items-center gap-1 rounded-lg px-3 py-1.5', view === 'week' ? 'bg-[#941B2B] text-white' : 'text-[#525960]']" @click="visit({ view: 'week' })">
                        <CalendarRange class="h-3.5 w-3.5" /> Semana
                    </button>
                    <button type="button" :class="['inline-flex items-center gap-1 rounded-lg px-3 py-1.5', view === 'list' ? 'bg-[#941B2B] text-white' : 'text-[#525960]']" @click="visit({ view: 'list' })">
                        <List class="h-3.5 w-3.5" /> Lista
                    </button>
                </div>

                <div v-if="view === 'week'" class="flex items-center gap-1">
                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-[#E3E5E8] hover:bg-[#F9ECEE]" title="Semana anterior" @click="shiftWeek(-7)">
                        <ChevronLeft class="h-4 w-4" />
                    </button>
                    <button type="button" class="h-9 rounded-lg border border-[#E3E5E8] px-3 text-xs font-semibold hover:bg-[#F9ECEE]" @click="visit({ date: today })">Hoy</button>
                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-[#E3E5E8] hover:bg-[#F9ECEE]" title="Semana siguiente" @click="shiftWeek(7)">
                        <ChevronRight class="h-4 w-4" />
                    </button>
                    <span class="ml-2 text-sm font-semibold text-[#292D32]">{{ weekLabel }}</span>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <form class="min-w-[200px] flex-1" @submit.prevent="visit()">
                    <input v-model="filters.search" type="search" placeholder="Buscar paciente..." class="h-[42px] w-full rounded-xl border border-[#E3E5E8] px-3 text-sm" />
                </form>
                <FilterSelect v-model="filters.clinic_id" placeholder="Todas las clínicas" :options="recordOptions(clinics)" @change="filters.space_id = ''; visit()" />
                <FilterSelect v-model="filters.space_id" placeholder="Todos los consultorios" :options="spaceOptions" @change="visit()" />
                <FilterSelect v-model="filters.student_profile_id" placeholder="Todos los alumnos" :options="studentOptions" @change="visit()" />
                <FilterSelect v-model="filters.status" placeholder="Todos los estados" :options="enumOptions(statuses)" @change="visit()" />
                <template v-if="view === 'list'">
                    <input v-model="filters.date_from" type="date" class="h-[42px] rounded-xl border border-[#E3E5E8] px-3 text-sm" title="Desde" @change="visit()" />
                    <input v-model="filters.date_to" type="date" class="h-[42px] rounded-xl border border-[#E3E5E8] px-3 text-sm" title="Hasta" @change="visit()" />
                </template>
            </div>
        </div>

        <!-- Semana -->
        <div v-if="view === 'week'" class="overflow-x-auto">
            <div class="grid min-w-[900px] gap-3" :style="{ gridTemplateColumns: `repeat(${days.length}, minmax(0, 1fr))` }">
                <div v-for="day in days" :key="day.iso" class="flex flex-col gap-2">
                    <div :class="['rounded-xl border px-3 py-2 text-center', day.iso === today ? 'border-[#941B2B] bg-[#F9ECEE]' : 'border-[#E3E5E8] bg-white']">
                        <p class="text-xs font-semibold uppercase text-[#858B93]">{{ day.name }}</p>
                        <p class="text-lg font-bold text-[#292D32]">{{ day.day }}</p>
                        <p class="text-[11px] text-[#858B93]">{{ day.items.length }} cita(s)</p>
                    </div>

                    <button
                        v-for="a in day.items"
                        :key="a.id"
                        type="button"
                        :class="['rounded-xl border border-l-4 border-[#E3E5E8] bg-white p-2.5 text-left transition hover:shadow-md', BORDERS[a.status], ['cancelada', 'reprogramada'].includes(a.status) ? 'opacity-60' : '']"
                        @click="openDetail(a)"
                    >
                        <p class="text-xs font-bold text-[#292D32]">{{ formatTime(a.start_time) }}–{{ formatTime(a.end_time) }}</p>
                        <p class="truncate text-sm font-semibold text-[#292D32]">{{ fullName(a.patient.people) }}</p>
                        <p class="truncate text-xs text-[#858B93]">{{ [a.space?.code, a.student ? fullName(a.student.people) : null].filter(Boolean).join(' · ') || a.clinic.name }}</p>
                        <p class="mt-1 text-[11px] font-semibold text-[#525960]">{{ label(a.status) }}</p>
                    </button>

                    <Link
                        v-if="can.create && day.iso >= today"
                        :href="admin.appointments.create({ query: { date: day.iso, clinic_id: filters.clinic_id || undefined, space_id: filters.space_id || undefined } }).url"
                        class="rounded-xl border border-dashed border-[#E3E5E8] py-2 text-center text-xs font-semibold text-[#858B93] hover:border-[#941B2B] hover:text-[#941B2B]"
                    >
                        + Agendar
                    </Link>
                </div>
            </div>
        </div>

        <!-- Lista -->
        <DataTable
            v-else-if="paginated"
            :pagination="paginated"
            :is-empty="paginated.data.length === 0"
            :empty-colspan="5"
            :show-search="false"
        >
            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Fecha</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Paciente</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Lugar</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Alumno</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Estado</th>
                </tr>
            </template>
            <template #table-body>
                <tr v-for="a in paginated.data" :key="a.id" class="cursor-pointer transition-colors hover:bg-[#FAF3F4]" @click="openDetail(a)">
                    <td class="px-5 py-4 sm:px-6">
                        <p class="font-semibold text-[#292D32]">{{ formatDate(a.date) }}</p>
                        <p class="text-xs text-[#858B93]">{{ formatTime(a.start_time) }}–{{ formatTime(a.end_time) }}</p>
                    </td>
                    <td class="px-5 py-4 sm:px-6">
                        <p class="font-semibold text-[#292D32]">{{ fullName(a.patient.people) }}</p>
                        <p class="text-xs text-[#858B93]">{{ a.patient.record_number }}<template v-if="a.reason"> · {{ a.reason }}</template></p>
                    </td>
                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ a.clinic.name }}<p v-if="a.space" class="text-xs text-[#858B93]">{{ a.space.name }}</p></td>
                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ a.student ? fullName(a.student.people) : '—' }}</td>
                    <td class="px-5 py-4 sm:px-6"><StatusBadge :value="a.status" /></td>
                </tr>
            </template>
            <template #empty>
                <EmptyState :icon="CalendarRange" title="Sin citas" description="No hay citas con esos filtros." />
            </template>
        </DataTable>

        <!-- Detalle de la cita -->
        <Dialog v-model:open="detailOpen">
            <DialogContent v-if="selected" class="max-h-[90vh] overflow-y-auto font-['DM_Sans',sans-serif] sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle class="font-['Manrope',sans-serif] text-base font-semibold text-[#941B2B]">
                        {{ fullName(selected.patient.people) }}
                    </DialogTitle>
                </DialogHeader>

                <div class="flex items-center gap-2"><StatusBadge :value="selected.status" /></div>

                <dl class="grid grid-cols-2 gap-4">
                    <DetailItem label="Fecha" :value="formatDate(selected.date)" />
                    <DetailItem label="Horario" :value="`${formatTime(selected.start_time)} – ${formatTime(selected.end_time)}`" />
                    <DetailItem label="Clínica" :value="selected.clinic.name" />
                    <DetailItem label="Consultorio" :value="selected.space ? `${selected.space.code} · ${selected.space.name}` : null" />
                    <DetailItem label="Alumno" :value="selected.student ? fullName(selected.student.people) : null" />
                    <DetailItem label="Docente" :value="selected.teacher ? fullName(selected.teacher.people) : null" />
                    <DetailItem label="Teléfono" :value="selected.patient.people.phone" />
                    <DetailItem label="Recordatorio" :value="selected.reminder_sent_at ? formatDateTime(selected.reminder_sent_at) : 'No enviado'" />
                </dl>
                <p v-if="selected.reason" class="text-sm text-[#383D43]"><span class="font-semibold">Motivo:</span> {{ selected.reason }}</p>
                <p v-if="selected.cancellation_reason" class="text-sm text-[#941B2B]"><span class="font-semibold">Cancelación:</span> {{ selected.cancellation_reason }}</p>
                <p v-if="selected.notes" class="whitespace-pre-line text-sm text-[#525960]">{{ selected.notes }}</p>

                <div class="flex flex-wrap gap-2 border-t border-[#E3E5E8] pt-4">
                    <template v-if="can.edit && isOpen(selected)">
                        <AdminButton v-if="selected.status === 'programada'" :icon="Check" @click="act('confirm')">Confirmar</AdminButton>
                        <AdminButton variant="secondary" :icon="CalendarCheck" @click="act('attend')">Atendida</AdminButton>
                        <AdminButton variant="secondary" :icon="UserX" @click="act('noShow')">No asistió</AdminButton>
                        <AdminButton variant="secondary" :icon="Bell" @click="act('remind')">
                            {{ selected.patient.people.personal_email ? 'Enviar recordatorio' : 'Marcar recordado' }}
                        </AdminButton>
                    </template>
                    <AdminButton v-if="can.edit && (isOpen(selected) || selected.status === 'no_asistio')" variant="secondary" :icon="RotateCcw" @click="openReschedule">Reprogramar</AdminButton>
                    <AdminButton v-if="can.edit && isOpen(selected)" variant="secondary" :icon="CalendarX" @click="openCancel">Cancelar</AdminButton>
                </div>

                <div class="flex flex-wrap gap-4 text-sm">
                    <Link :href="admin.patients.show(selected.patient.id).url" class="inline-flex items-center gap-1.5 font-semibold text-[#941B2B] hover:underline">
                        <Contact class="h-4 w-4" /> Ver expediente
                    </Link>
                    <a v-if="selected.patient.people.phone" :href="`tel:${selected.patient.people.phone}`" class="inline-flex items-center gap-1.5 font-semibold text-[#525960] hover:underline">
                        <Phone class="h-4 w-4" /> Llamar
                    </a>
                    <Link v-if="can.edit && isOpen(selected)" :href="admin.appointments.edit(selected.id).url" class="inline-flex items-center gap-1.5 font-semibold text-[#525960] hover:underline">
                        <Pencil class="h-4 w-4" /> Editar
                    </Link>
                    <button v-if="can.delete" type="button" class="inline-flex items-center gap-1.5 font-semibold text-[#941B2B] hover:underline" @click="destroy">
                        <Trash2 class="h-4 w-4" /> Eliminar
                    </button>
                </div>
            </DialogContent>
        </Dialog>

        <FormDialog v-model:open="cancelOpen" title="Cancelar cita" submit-label="Cancelar cita" :loading="cancelForm.processing" @submit="submitCancel">
            <div class="md:col-span-2">
                <FormInput id="cancel_reason" v-model="cancelForm.cancellation_reason" label="Motivo de la cancelación" placeholder="Ej. El paciente avisó que no puede" :error="cancelForm.errors.cancellation_reason" required />
            </div>
        </FormDialog>

        <FormDialog v-model:open="rescheduleOpen" title="Reprogramar cita" description="Se crea una cita nueva y la actual queda como reprogramada." submit-label="Reprogramar" :loading="rescheduleForm.processing" @submit="submitReschedule">
            <FormInput id="rs_date" v-model="rescheduleForm.date" type="date" label="Nueva fecha" :min="today" :error="rescheduleForm.errors.date" required />
            <FormSelect id="rs_space" v-model="rescheduleForm.space_id" label="Consultorio" placeholder="Sin asignar" :options="rescheduleSpaces" :error="rescheduleForm.errors.space_id" nullable />
            <FormInput id="rs_start" v-model="rescheduleForm.start_time" type="time" label="Inicio" :error="rescheduleForm.errors.start_time" required />
            <FormInput id="rs_end" v-model="rescheduleForm.end_time" type="time" label="Fin" :error="rescheduleForm.errors.end_time" required />
            <p v-if="rescheduleForm.errors.student_profile_id" class="form-error md:col-span-2">{{ rescheduleForm.errors.student_profile_id }}</p>
        </FormDialog>

        <AdminCard v-if="view === 'week' && !weekList.length" :padding="false">
            <EmptyState :icon="CalendarRange" title="Semana sin citas" description="No hay citas en esta semana con los filtros elegidos." />
        </AdminCard>
    </div>
</template>
