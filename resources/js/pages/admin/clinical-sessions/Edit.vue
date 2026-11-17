<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CalendarCheck } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import ClinicalSessionForm from '@/components/admin/forms/ClinicalSessionForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { formatDate, formatTime, toDateInput } from '@/lib/admin';
import admin from '@/routes/admin';
import type { ClinicalSessionFormData, ClinicalSessionFormOptions } from '@/types/forms';

const props = defineProps<ClinicalSessionFormOptions & {
    session: Omit<ClinicalSessionFormData, 'topic' | 'notes'> & { id: number; topic: string | null; notes: string | null };
}>();

const { session, ...options } = props;

const form = useForm<ClinicalSessionFormData>({
    clinic_id: session.clinic_id,
    space_id: session.space_id,
    academic_period_id: session.academic_period_id,
    academic_group_id: session.academic_group_id,
    subject_id: session.subject_id,
    supervisor_id: session.supervisor_id,
    date: toDateInput(session.date),
    start_time: formatTime(session.start_time),
    end_time: formatTime(session.end_time),
    status: session.status,
    topic: session.topic ?? '',
    notes: session.notes ?? '',
});

const submit = (): void => {
    form.put(admin.clinicalSessions.update(session.id).url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Sesiones clínicas', href: admin.clinicalSessions.index() },
            { title: 'Editar', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar sesión clínica" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Editar sesión clínica"
            :description="`${formatDate(session.date)} · ${formatTime(session.start_time)}`"
            section="Clínicas"
            :icon="CalendarCheck"
            :action-icon="ArrowLeft"
            action-label="Volver a la sesión"
            :action-href="admin.clinicalSessions.show(session.id).url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <ClinicalSessionForm :form="form" v-bind="options" />

            <FormActions :cancel-href="admin.clinicalSessions.show(session.id).url" :loading="form.processing" />
        </form>
    </div>
</template>
