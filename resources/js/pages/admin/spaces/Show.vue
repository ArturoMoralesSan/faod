<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CalendarClock, DoorOpen, Info, Pencil } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import AdminCard from '@/components/admin/AdminCard.vue';
import DetailItem from '@/components/admin/DetailItem.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import WeeklySchedule from '@/components/admin/WeeklySchedule.vue';
import { fullName, label, recordOptions } from '@/lib/admin';
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
        clinic: Option | null;
    };
    schedules: Schedule[];
    days: Days;
    periods: Option[];
    academicPeriodId: number | null;
}>();

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
            <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5">
                <DetailItem label="Tipo" :value="label(space.type)" />
                <DetailItem label="Clínica" :value="space.clinic?.name" />
                <DetailItem label="Edificio" :value="space.building" />
                <DetailItem label="Capacidad" :value="space.capacity" />
                <DetailItem label="Estado"><StatusBadge :value="space.is_active" /></DetailItem>
            </dl>
        </AdminCard>

        <AdminCard title="Ocupación semanal" description="Clases programadas en este espacio." :icon="CalendarClock">
            <div class="mb-4 max-w-xs">
                <FilterSelect v-model="period" placeholder="Selecciona un periodo" :options="recordOptions(periods)" @change="changePeriod" />
            </div>

            <WeeklySchedule :blocks="blocks" :days="days" />
        </AdminCard>
    </div>
</template>
