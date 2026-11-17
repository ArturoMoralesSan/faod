<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CalendarCheck } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import ClinicalSessionForm from '@/components/admin/forms/ClinicalSessionForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import admin from '@/routes/admin';
import type { ClinicalSessionFormData, ClinicalSessionFormOptions } from '@/types/forms';

const props = defineProps<ClinicalSessionFormOptions & {
    /** Valores al llegar desde una clínica o consultorio. */
    defaults: { clinic_id?: string; space_id?: string; date?: string };
}>();

const { defaults, ...options } = props;

const toId = (value?: string) => (value ? Number(value) : null);

const form = useForm<ClinicalSessionFormData>({
    clinic_id: toId(defaults.clinic_id) ?? (options.clinics.length === 1 ? options.clinics[0].id : null),
    space_id: toId(defaults.space_id),
    academic_period_id: null,
    academic_group_id: null,
    subject_id: null,
    supervisor_id: null,
    date: defaults.date ??new Date().toISOString().substring(0, 10),
    start_time: '08:00',
    end_time: '10:00',
    status: 'programada',
    topic: '',
    notes: '',
});

const submit = (): void => {
    form.post(admin.clinicalSessions.store().url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Sesiones clínicas', href: admin.clinicalSessions.index() },
            { title: 'Nueva', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Nueva sesión clínica" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Programar sesión clínica"
            description="Práctica supervisada en una clínica de la facultad."
            section="Clínicas"
            :icon="CalendarCheck"
            :action-icon="ArrowLeft"
            action-label="Volver a sesiones"
            :action-href="admin.clinicalSessions.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <ClinicalSessionForm :form="form" v-bind="options" />

            <FormActions
                :cancel-href="admin.clinicalSessions.index().url"
                submit-label="Programar sesión"
                loading-text="Guardando..."
                :loading="form.processing"
            />
        </form>
    </div>
</template>
