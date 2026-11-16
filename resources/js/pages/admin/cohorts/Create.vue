<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, UsersRound } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import CohortForm from '@/components/admin/forms/CohortForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import admin from '@/routes/admin';
import type { Option } from '@/types/admin';
import type { CohortFormData } from '@/types/forms';

defineProps<{
    statuses: string[];
    programs: Option[];
}>();

const form = useForm<CohortFormData>({
    academic_program_id: null,
    name: '',
    starts_at: '',
    ends_at: '',
    status: 'planeada',
});

const submit = (): void => {
    form.post(admin.cohorts.store().url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Generaciones', href: admin.cohorts.index() },
            { title: 'Nueva', href: admin.cohorts.create() },
        ],
    },
});
</script>

<template>
    <Head title="Nueva generación" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Nueva generación"
            description="Registra una generación de un programa académico."
            section="Programas académicos"
            :icon="UsersRound"
            :action-icon="ArrowLeft"
            action-label="Volver a generaciones"
            :action-href="admin.cohorts.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <CohortForm :form="form" :statuses="statuses" :programs="programs" />

            <FormActions
                :cancel-href="admin.cohorts.index().url"
                submit-label="Crear generación"
                loading-text="Creando..."
                :loading="form.processing"
            />
        </form>
    </div>
</template>
