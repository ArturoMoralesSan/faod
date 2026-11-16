<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import AcademicPeriodForm from '@/components/admin/forms/AcademicPeriodForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import admin from '@/routes/admin';
import type { AcademicPeriodFormData } from '@/types/forms';

const form = useForm<AcademicPeriodFormData>({
    code: '',
    name: '',
    starts_at: '',
    ends_at: '',
    is_current: false,
});

const submit = (): void => {
    form.post(admin.academicPeriods.store().url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Periodos académicos', href: admin.academicPeriods.index() },
            { title: 'Nuevo', href: admin.academicPeriods.create() },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo periodo" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Nuevo periodo académico"
            description="Registra un semestre o periodo escolar."
            section="Programas académicos"
            :icon="CalendarDays"
            :action-icon="ArrowLeft"
            action-label="Volver a periodos"
            :action-href="admin.academicPeriods.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <AcademicPeriodForm :form="form" />

            <FormActions
                :cancel-href="admin.academicPeriods.index().url"
                submit-label="Crear periodo"
                loading-text="Creando..."
                :loading="form.processing"
            />
        </form>
    </div>
</template>
