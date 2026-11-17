<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, TriangleAlert } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import ClinicIncidentForm from '@/components/admin/forms/ClinicIncidentForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { toDateTimeInput } from '@/lib/admin';
import admin from '@/routes/admin';
import type { ClinicIncidentFormData, ClinicIncidentFormOptions } from '@/types/forms';

const props = defineProps<ClinicIncidentFormOptions & {
    /** Valores al llegar desde una clínica, equipo o sesión. */
    defaults: { clinic_id?: string; space_id?: string; clinic_equipment_id?: string; clinical_session_id?: string };
}>();

const { defaults, ...options } = props;

const toId = (value?: string) => (value ? Number(value) : null);

const form = useForm<ClinicIncidentFormData>({
    clinic_id: toId(defaults.clinic_id) ?? (options.clinics.length === 1 ? options.clinics[0].id : null),
    space_id: toId(defaults.space_id),
    clinic_equipment_id: toId(defaults.clinic_equipment_id),
    clinical_session_id: toId(defaults.clinical_session_id),
    type: defaults.clinic_equipment_id ? 'equipo' : 'instalaciones',
    severity: 'media',
    status: 'abierta',
    title: '',
    description: '',
    occurred_at: toDateTimeInput(new Date().toISOString()),
    resolution: '',
});

const submit = (): void => {
    form.post(admin.clinicIncidents.store().url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Incidencias', href: admin.clinicIncidents.index() },
            { title: 'Nueva', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Reportar incidencia" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Reportar incidencia"
            description="Fallas de equipo, instalaciones, bioseguridad u otros eventos en una clínica."
            section="Clínicas"
            :icon="TriangleAlert"
            :action-icon="ArrowLeft"
            action-label="Volver a incidencias"
            :action-href="admin.clinicIncidents.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <ClinicIncidentForm :form="form" v-bind="options" />

            <FormActions
                :cancel-href="admin.clinicIncidents.index().url"
                submit-label="Registrar incidencia"
                loading-text="Guardando..."
                :loading="form.processing"
            />
        </form>
    </div>
</template>
