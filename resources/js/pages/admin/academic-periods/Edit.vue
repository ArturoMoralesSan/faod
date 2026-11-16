<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import AcademicPeriodForm from '@/components/admin/forms/AcademicPeriodForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { toDateInput } from '@/lib/admin';
import admin from '@/routes/admin';
import type { AcademicPeriodFormData } from '@/types/forms';

const props = defineProps<{
    period: AcademicPeriodFormData & { id: number };
}>();

const form = useForm<AcademicPeriodFormData>({
    code: props.period.code,
    name: props.period.name,
    starts_at: toDateInput(props.period.starts_at),
    ends_at: toDateInput(props.period.ends_at),
    is_current: props.period.is_current,
});

const submit = (): void => {
    form.put(admin.academicPeriods.update(props.period.id).url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Periodos académicos', href: admin.academicPeriods.index() },
            { title: 'Editar', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar periodo" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Editar periodo académico"
            :description="period.name"
            section="Programas académicos"
            :icon="CalendarDays"
            :action-icon="ArrowLeft"
            action-label="Volver a periodos"
            :action-href="admin.academicPeriods.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <AcademicPeriodForm :form="form" />

            <FormActions :cancel-href="admin.academicPeriods.index().url" :loading="form.processing" />
        </form>
    </div>
</template>
