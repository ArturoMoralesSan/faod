<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { CalendarCheck, CalendarClock, Clock, DoorOpen, History, Info, Pencil, Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import AdminCard from '@/components/admin/AdminCard.vue';
import DetailItem from '@/components/admin/DetailItem.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import RecordList from '@/components/admin/TeacherRecordList.vue';
import WeeklySchedule from '@/components/admin/WeeklySchedule.vue';
import { confirmDelete, formatDate, formatTime, fullName, label, recordOptions } from '@/lib/admin';
import PrintLink from '@/components/admin/PrintLink.vue';
import admin from '@/routes/admin';
import type { Days, Option, PersonName, ScheduleBlock } from '@/types/admin';

interface Schedule {
    id: number;
    day_of_week: number;
    start_time: string;
    end_time: string;
    class_section: {
        subject_id: number;
        subject: { id: number; code: string; name: string };
        group: { id: number; code: string };
        teacher: { people: PersonName | null } | null;
    };
}

interface Availability {
    id: number;
    academic_period_id: number | null;
    day_of_week: number;
    start_time: string;
    end_time: string;
    notes: string | null;
    academic_period: Option | null;
}

const props = defineProps<{
    space: {
        id: number;
        code: string;
        name: string;
        type: string;
        building: string | null;
        floor: string | null;
        capacity: number | null;
        is_active: boolean;
        clinic_id: number | null;
        clinic: Option | null;
        area: Option | null;
        availabilities: Availability[];
    };
    schedules: Schedule[];
    upcomingSessions: {
        id: number;
        date: string;
        start_time: string;
        end_time: string;
        status: string;
        topic: string | null;
        group: { code: string } | null;
        supervisor: { people: PersonName | null } | null;
    }[];
    days: Days;
    periods: Option[];
    academicPeriodId: number | null;
    usage: { kind: 'sesion' | 'cita' | 'actividad' | 'incidencia'; date: string | null; time: string | null; title: string; detail: string; status: string }[];
}>();

const USAGE_KINDS: Record<string, { label: string; classes: string }> = {
    sesion: { label: 'Sesión clínica', classes: 'bg-[#EAF1FB] text-[#2457A6]' },
    cita: { label: 'Cita', classes: 'bg-[#E2F3E8] text-[#187744]' },
    actividad: { label: 'Actividad', classes: 'bg-[#F3EDFB] text-[#5B3A9B]' },
    incidencia: { label: 'Incidencia', classes: 'bg-[#F9ECEE] text-[#941B2B]' },
};

const usageCounts = computed(() =>
    Object.keys(USAGE_KINDS).map((kind) => ({ kind, count: props.usage.filter((u) => u.kind === kind).length })).filter((row) => row.count),
);

const period = ref(props.academicPeriodId ? String(props.academicPeriodId) : '');

const changePeriod = (): void => {
    router.get(
        admin.spaces.show(props.space.id).url,
        { academic_period_id: period.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const blocks = computed<ScheduleBlock[]>(() =>
    props.schedules.map((schedule) => ({
        id: schedule.id,
        day_of_week: schedule.day_of_week,
        start_time: schedule.start_time,
        end_time: schedule.end_time,
        title: schedule.class_section.subject.name,
        subtitle: `Grupo ${schedule.class_section.group.code}`,
        meta: schedule.class_section.teacher ? fullName(schedule.class_section.teacher.people) : 'Sin docente',
        colorKey: schedule.class_section.subject.id,
    })),
);

/* ---------------------- Disponibilidad del consultorio ---------------------- */

const dayOptions = computed(() =>
    Object.entries(props.days).map(([value, name]) => ({ value: Number(value), label: name })),
);

const dialogOpen = ref(false);
const editingId = ref<number | null>(null);

const emptyAvailability = {
    academic_period_id: null as number | null,
    day_of_week: 1 as number | null,
    start_time: '08:00',
    end_time: '14:00',
    notes: '',
};

const availabilityForm = useForm({ ...emptyAvailability });

const openAvailability = (id: number | null = null): void => {
    editingId.value = id;
    availabilityForm.defaults({ ...emptyAvailability });
    availabilityForm.reset();
    availabilityForm.clearErrors();

    const record = id ? props.space.availabilities.find((a) => a.id === id) : null;

    if (record) {
        availabilityForm.academic_period_id = record.academic_period_id;
        availabilityForm.day_of_week = record.day_of_week;
        availabilityForm.start_time = formatTime(record.start_time);
        availabilityForm.end_time = formatTime(record.end_time);
        availabilityForm.notes = record.notes ?? '';
    }

    dialogOpen.value = true;
};

const submitAvailability = (): void => {
    const options = { preserveScroll: true, onSuccess: () => (dialogOpen.value = false) };

    availabilityForm.transform((data) => ({ ...data, notes: data.notes || null }));

    if (editingId.value) {
        availabilityForm.put(
            admin.spaces.availabilities.update({ space: props.space.id, availability: editingId.value }).url,
            options,
        );
    } else {
        availabilityForm.post(admin.spaces.availabilities.store(props.space.id).url, options);
    }
};

const removeAvailability = (id: number): void => {
    confirmDelete(
        '¿Eliminar horario de disponibilidad?',
        'Esta acción no se puede deshacer.',
        admin.spaces.availabilities.destroy({ space: props.space.id, availability: id }).url,
        { preserveState: true },
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Espacios', href: admin.spaces.index() },
            { title: 'Detalle', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="space.name" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            :title="space.name"
            :description="`${label(space.type)} · ${space.code}`"
            section="Espacios"
            :icon="DoorOpen"
            :action-icon="Pencil"
            action-label="Editar espacio"
            :action-href="admin.spaces.edit(space.id).url"
        />

        <AdminCard title="Información general" :icon="Info">
            <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-6">
                <DetailItem label="Tipo" :value="label(space.type)" />
                <DetailItem label="Clínica">
                    <Link v-if="space.clinic" :href="admin.clinics.show(space.clinic.id).url" class="text-sm font-semibold text-[#941B2B] hover:underline">
                        {{ space.clinic.name }}
                    </Link>
                    <span v-else class="text-sm text-[#858B93]">—</span>
                </DetailItem>
                <DetailItem label="Área" :value="space.area?.name" />
                <DetailItem label="Edificio" :value="space.building" />
                <DetailItem label="Capacidad" :value="space.capacity" />
                <DetailItem label="Estado"><StatusBadge :value="space.is_active" /></DetailItem>
            </dl>
        </AdminCard>

        <div class="max-w-xs">
            <FilterSelect v-model="period" placeholder="Selecciona un periodo" :options="recordOptions(periods)" @change="changePeriod" />
        </div>

        <div class="grid gap-5 xl:grid-cols-3">
            <div class="flex flex-col gap-5 xl:col-span-2">
                <AdminCard title="Ocupación semanal" description="Clases programadas en este espacio." :icon="CalendarClock">
                    <WeeklySchedule :blocks="blocks" :days="days" />

                    <template v-if="blocks.length" #footer>
                        <PrintLink :href="admin.spaces.schedulePdf(space.id, { query: { academic_period_id: academicPeriodId ?? undefined } }).url" />
                    </template>
                </AdminCard>

                <AdminCard v-if="space.clinic_id" title="Próximas sesiones clínicas" :icon="CalendarCheck" :padding="false">
                    <div class="flex justify-end border-b border-[#E3E5E8] px-5 py-3 sm:px-6">
                        <Link
                            :href="admin.clinicalSessions.create({ query: { clinic_id: space.clinic_id, space_id: space.id } }).url"
                            class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#941B2B] hover:underline"
                        >
                            <Plus class="h-4 w-4" /> Programar sesión
                        </Link>
                    </div>

                    <ul v-if="upcomingSessions.length" class="divide-y divide-[#E3E5E8]">
                        <li v-for="session in upcomingSessions" :key="session.id">
                            <Link
                                :href="admin.clinicalSessions.show(session.id).url"
                                class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-[#FAF3F4] sm:px-6"
                            >
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-[#292D32]">
                                        {{ formatDate(session.date) }} · {{ formatTime(session.start_time) }}–{{ formatTime(session.end_time) }}
                                    </p>
                                    <p class="truncate text-xs text-[#858B93]">
                                        {{ [session.topic, session.group ? `Grupo ${session.group.code}` : null, session.supervisor ? fullName(session.supervisor.people) : null].filter(Boolean).join(' · ') || 'Sin detalle' }}
                                    </p>
                                </div>
                                <StatusBadge :value="session.status" />
                            </Link>
                        </li>
                    </ul>

                    <p v-else class="px-5 py-4 text-sm italic text-[#858B93] sm:px-6">Sin sesiones próximas.</p>
                </AdminCard>

                <AdminCard title="Historial de uso" description="Últimos 90 días: sesiones clínicas, citas, actividades e incidencias." :icon="History" :padding="false">
                    <div v-if="usageCounts.length" class="flex flex-wrap gap-2 border-b border-[#E3E5E8] px-5 py-3 sm:px-6">
                        <span v-for="row in usageCounts" :key="row.kind" class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="USAGE_KINDS[row.kind].classes">
                            {{ row.count }} {{ USAGE_KINDS[row.kind].label.toLowerCase() }}{{ row.count === 1 ? '' : '(s)' }}
                        </span>
                    </div>
                    <ul v-if="usage.length" class="divide-y divide-[#E3E5E8]">
                        <li v-for="(u, index) in usage" :key="index" class="flex items-center justify-between gap-3 px-5 py-3 sm:px-6">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-[#292D32]">
                                    <span class="mr-1 rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="USAGE_KINDS[u.kind].classes">{{ USAGE_KINDS[u.kind].label }}</span>
                                    {{ u.title }}
                                </p>
                                <p class="truncate text-xs text-[#858B93]">{{ [formatDate(u.date), u.time, u.detail].filter(Boolean).join(' · ') }}</p>
                            </div>
                            <StatusBadge :value="u.status" />
                        </li>
                    </ul>
                    <p v-else class="px-5 py-4 text-sm italic text-[#858B93] sm:px-6">Sin uso registrado en los últimos 90 días.</p>
                </AdminCard>
            </div>

            <div class="flex flex-col gap-5">
                <RecordList
                    title="Horario de atención"
                    :icon="Clock"
                    :items="space.availabilities.map((a) => ({
                        id: a.id,
                        title: `${days[a.day_of_week]} ${formatTime(a.start_time)} – ${formatTime(a.end_time)}`,
                        subtitle: a.academic_period?.name ?? 'Todos los periodos',
                        meta: a.notes ?? undefined,
                    }))"
                    empty-text="Sin horario definido: se puede usar en cualquier horario libre."
                    add-label="Agregar horario"
                    @add="openAvailability()"
                    @edit="(id) => openAvailability(id)"
                    @remove="(id) => removeAvailability(id)"
                />
            </div>
        </div>

        <FormDialog
            v-model:open="dialogOpen"
            :title="`${editingId ? 'Editar' : 'Agregar'} horario de atención`"
            description="Las sesiones clínicas en este espacio deberán caber en alguno de estos horarios."
            :loading="availabilityForm.processing"
            @submit="submitAvailability"
        >
            <FormSelect id="av_day" v-model="availabilityForm.day_of_week" label="Día" :options="dayOptions" :error="availabilityForm.errors.day_of_week" required />
            <FormSelect
                id="av_period"
                v-model="availabilityForm.academic_period_id"
                label="Periodo"
                placeholder="Todos los periodos"
                :options="recordOptions(periods)"
                :error="availabilityForm.errors.academic_period_id"
                nullable
            />
            <FormInput id="av_start" v-model="availabilityForm.start_time" type="time" label="Desde" :error="availabilityForm.errors.start_time" required />
            <FormInput id="av_end" v-model="availabilityForm.end_time" type="time" label="Hasta" :error="availabilityForm.errors.end_time" required />
            <div class="md:col-span-2">
                <FormInput id="av_notes" v-model="availabilityForm.notes" label="Notas" :error="availabilityForm.errors.notes" />
            </div>
        </FormDialog>
    </div>
</template>
