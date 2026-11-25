<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CalendarClock } from 'lucide-vue-next';
import { ref } from 'vue';

import AdminCard from '@/components/admin/AdminCard.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import PrintLink from '@/components/admin/PrintLink.vue';
import WeeklySchedule from '@/components/admin/WeeklySchedule.vue';
import { fullName, recordOptions } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Days, PersonName, ScheduleBlock } from '@/types/admin';

interface Section {
    id: number;
    subject: { id: number; code: string; name: string };
    teacher: { people: PersonName } | null;
    schedules: { id: number; day_of_week: number; start_time: string; end_time: string; space: { code: string; name: string } | null }[];
}

interface Group {
    id: number;
    code: string;
    period_number: number;
    cohort: { name: string; academic_program: { name: string } | null } | null;
    class_sections: Section[];
}

const props = defineProps<{
    groups: Group[];
    days: Days;
    academicPeriodId: number | null;
    periodName: string | null;
    periods: { id: number; name: string }[];
    hasProfile: boolean;
}>();

const period = ref<string>(props.academicPeriodId ? String(props.academicPeriodId) : '');

const changePeriod = () =>
    router.get(admin.mySchedule.index().url, { academic_period_id: period.value || undefined }, { preserveScroll: true });

const blocks = (group: Group): ScheduleBlock[] =>
    group.class_sections.flatMap((section) =>
        section.schedules.map((schedule) => ({
            id: schedule.id,
            day_of_week: schedule.day_of_week,
            start_time: schedule.start_time,
            end_time: schedule.end_time,
            title: section.subject.name,
            subtitle: section.teacher ? fullName(section.teacher.people) : 'Sin docente',
            meta: schedule.space?.code,
            colorKey: section.subject.id,
        })),
    );

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Mi horario', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Mi horario" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader title="Mi horario" :description="periodName ?? 'Sin periodo vigente'" section="Académico" :icon="CalendarClock" />

        <div class="max-w-xs">
            <FilterSelect v-model="period" placeholder="Selecciona un periodo" :options="recordOptions(periods)" @change="changePeriod" />
        </div>

        <AdminCard v-if="!hasProfile" title="Sin expediente de alumno" :icon="CalendarClock">
            <p class="text-sm text-[#525960]">Tu cuenta no está ligada a un expediente de alumno. Pide a Servicios Escolares que la vincule.</p>
        </AdminCard>

        <AdminCard v-else-if="!groups.length" title="Sin grupo asignado" :icon="CalendarClock">
            <p class="text-sm text-[#525960]">No estás inscrito en ningún grupo en este periodo.</p>
        </AdminCard>

        <AdminCard
            v-for="group in groups"
            :key="group.id"
            :title="`Grupo ${group.code}`"
            :description="[group.cohort?.academic_program?.name, `Semestre ${group.period_number}`].filter(Boolean).join(' · ')"
            :icon="CalendarClock"
        >
            <WeeklySchedule :blocks="blocks(group)" :days="days" />

            <template v-if="blocks(group).length" #footer>
                <PrintLink :href="admin.mySchedule.pdf(group.id).url" />
            </template>
        </AdminCard>
    </div>
</template>
