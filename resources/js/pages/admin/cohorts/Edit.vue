<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, UsersRound } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import CohortForm from '@/components/admin/forms/CohortForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { toDateInput } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option } from '@/types/admin';
import type { CohortFormData } from '@/types/forms';

const props = defineProps<{
    cohort: CohortFormData & { id: number };
    statuses: string[];
    programs: Option[];
}>();

const form = useForm<CohortFormData>({
    academic_program_id: props.cohort.academic_program_id,
    name: props.cohort.name,
    starts_at: toDateInput(props.cohort.starts_at),
    ends_at: toDateInput(props.cohort.ends_at),
    status: props.cohort.status,
});

const submit = (): void => {
    form.put(admin.cohorts.update(props.cohort.id).url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Generaciones', href: admin.cohorts.index() },
            { title: 'Editar', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar generación" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Editar generación"
            :description="cohort.name"
            section="Programas académicos"
            :icon="UsersRound"
            :action-icon="ArrowLeft"
            action-label="Volver a generaciones"
            :action-href="admin.cohorts.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <CohortForm :form="form" :statuses="statuses" :programs="programs" />

            <FormActions :cancel-href="admin.cohorts.index().url" :loading="form.processing" />
        </form>
    </div>
</template>
