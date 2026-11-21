<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Presentation } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import ActivityForm from '@/components/admin/forms/ActivityForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { toDateInput } from '@/lib/admin';
import admin from '@/routes/admin';
import type { ActivityFormData, ActivityFormOptions } from '@/types/forms';

// Los campos de texto pueden llegar como null desde el servidor.
const props = defineProps<ActivityFormOptions & { activity: ActivityFormData & { id: number } }>();

const { activity, ...options } = props;
const text = (v: string | null | undefined) => v ?? '';

const form = useForm<ActivityFormData>({
    type: text(activity.type),
    title: text(activity.title),
    description: text(activity.description),
    objectives: text(activity.objectives),
    modality: text(activity.modality),
    starts_at: toDateInput(activity.starts_at),
    ends_at: toDateInput(activity.ends_at),
    schedule: text(activity.schedule),
    space_id: activity.space_id,
    location: text(activity.location),
    capacity: activity.capacity,
    hours: activity.hours,
    cost: activity.cost,
    status: text(activity.status),
    coordinator_id: activity.coordinator_id,
    issues_certificate: Boolean(activity.issues_certificate),
    min_attendance: activity.min_attendance,
    evaluation_score: activity.evaluation_score,
    evaluation_notes: text(activity.evaluation_notes),
    results: text(activity.results),
});

const submit = (): void => {
    form.transform((data) => Object.fromEntries(Object.entries(data).map(([k, v]) => [k, v === '' ? null : v])))
        .put(admin.activities.update(activity.id).url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Cursos y actividades', href: admin.activities.index() },
            { title: 'Editar', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar actividad" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Editar actividad"
            :description="activity.title ?? ''"
            section="Académico"
            :icon="Presentation"
            :action-icon="ArrowLeft"
            action-label="Volver"
            :action-href="admin.activities.show(activity.id).url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <ActivityForm :form="form" v-bind="options" />
            <FormActions :cancel-href="admin.activities.show(activity.id).url" :loading="form.processing" />
        </form>
    </div>
</template>
