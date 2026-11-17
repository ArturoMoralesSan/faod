<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CalendarRange } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import AppointmentForm from '@/components/admin/forms/AppointmentForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { todayInput } from '@/lib/admin';
import admin from '@/routes/admin';
import type { AppointmentFormData, AppointmentFormOptions } from '@/types/forms';

const props = defineProps<AppointmentFormOptions & {
    defaults: {
        patient_id: number | null;
        patient_label: string | null;
        clinic_id?: string | number | null;
        space_id?: string;
        date?: string;
        start_time?: string;
        student_profile_id?: string;
    };
}>();

const { defaults, ...options } = props;
const toId = (value?: string | number | null) => (value ? Number(value) : null);

// Una hora después del inicio.
const plusHour = (time: string) => `${String((Number(time.substring(0, 2)) + 1) % 24).padStart(2, '0')}${time.substring(2, 5)}`;
const start = defaults.start_time?.substring(0, 5) ?? '09:00';

const form = useForm<AppointmentFormData>({
    patient_id: defaults.patient_id,
    clinic_id: toId(defaults.clinic_id) ?? (options.clinics.length === 1 ? options.clinics[0].id : null),
    space_id: toId(defaults.space_id),
    student_profile_id: toId(defaults.student_profile_id),
    teacher_profile_id: null,
    treatment_plan_id: null,
    date: defaults.date ?? todayInput(),
    start_time: start,
    end_time: plusHour(start),
    reason: '',
    notes: '',
});

const submit = (): void => {
    form.transform((data) => ({ ...data, reason: data.reason || null, notes: data.notes || null }))
        .post(admin.appointments.store().url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Agenda', href: admin.appointments.index() },
            { title: 'Nueva cita', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Nueva cita" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Agendar cita"
            description="El sistema avisa si el consultorio, el alumno o el paciente ya tienen algo en ese horario."
            section="Agenda"
            :icon="CalendarRange"
            :action-icon="ArrowLeft"
            action-label="Volver a la agenda"
            :action-href="admin.appointments.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <AppointmentForm :form="form" :patient-label="defaults.patient_label" v-bind="options" />

            <FormActions :cancel-href="admin.appointments.index().url" submit-label="Agendar cita" loading-text="Guardando..." :loading="form.processing" />
        </form>
    </div>
</template>
