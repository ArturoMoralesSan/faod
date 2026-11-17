<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CalendarRange } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import AppointmentForm from '@/components/admin/forms/AppointmentForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { formatTime, toDateInput } from '@/lib/admin';
import admin from '@/routes/admin';
import type { AppointmentFormData, AppointmentFormOptions } from '@/types/forms';

const props = defineProps<AppointmentFormOptions & {
    appointment: Omit<AppointmentFormData, 'reason' | 'notes'> & { id: number; reason: string | null; notes: string | null };
    patientLabel: string;
}>();

const { appointment, patientLabel, ...options } = props;

const form = useForm<AppointmentFormData>({
    patient_id: appointment.patient_id,
    clinic_id: appointment.clinic_id,
    space_id: appointment.space_id,
    student_profile_id: appointment.student_profile_id,
    teacher_profile_id: appointment.teacher_profile_id,
    treatment_plan_id: appointment.treatment_plan_id,
    date: toDateInput(appointment.date),
    start_time: formatTime(appointment.start_time),
    end_time: formatTime(appointment.end_time),
    reason: appointment.reason ?? '',
    notes: appointment.notes ?? '',
});

const backUrl = admin.appointments.index({ query: { date: toDateInput(appointment.date), clinic_id: appointment.clinic_id } }).url;

const submit = (): void => {
    form.transform((data) => ({ ...data, reason: data.reason || null, notes: data.notes || null }))
        .put(admin.appointments.update(appointment.id).url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Agenda', href: admin.appointments.index() },
            { title: 'Editar cita', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar cita" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Editar cita"
            :description="patientLabel"
            section="Agenda"
            :icon="CalendarRange"
            :action-icon="ArrowLeft"
            action-label="Volver a la agenda"
            :action-href="backUrl"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <AppointmentForm :form="form" :patient-label="patientLabel" lock-patient v-bind="options" />

            <FormActions :cancel-href="backUrl" :loading="form.processing" />
        </form>
    </div>
</template>
