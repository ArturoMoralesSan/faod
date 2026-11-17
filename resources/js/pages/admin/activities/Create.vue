<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Presentation } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import ActivityForm from '@/components/admin/forms/ActivityForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { todayInput } from '@/lib/admin';
import admin from '@/routes/admin';
import type { ActivityFormData, ActivityFormOptions } from '@/types/forms';

const props = defineProps<ActivityFormOptions>();

const form = useForm<ActivityFormData>({
    type: 'curso',
    title: '',
    description: '',
    modality: 'presencial',
    starts_at: todayInput(),
    ends_at: '',
    schedule: '',
    space_id: null,
    location: '',
    capacity: null,
    hours: null,
    cost: null,
    status: 'planeada',
    coordinator_id: null,
    issues_certificate: true,
    min_attendance: 80,
});

const submit = (): void => {
    form.transform((data) => Object.fromEntries(Object.entries(data).map(([k, v]) => [k, v === '' ? null : v])))
        .post(admin.activities.store().url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Cursos y actividades', href: admin.activities.index() },
            { title: 'Nueva', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Nueva actividad" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Nueva actividad académica"
            description="Curso, seminario, congreso, taller u otra actividad."
            section="Académico"
            :icon="Presentation"
            :action-icon="ArrowLeft"
            action-label="Volver"
            :action-href="admin.activities.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <ActivityForm :form="form" v-bind="props" />
            <FormActions :cancel-href="admin.activities.index().url" submit-label="Crear actividad" loading-text="Guardando..." :loading="form.processing" />
        </form>
    </div>
</template>
