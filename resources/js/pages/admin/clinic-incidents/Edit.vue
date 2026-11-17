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
    incident: Omit<ClinicIncidentFormData, 'resolution'> & { id: number; resolution: string | null };
}>();

const { incident, ...options } = props;

const form = useForm<ClinicIncidentFormData>({
    clinic_id: incident.clinic_id,
    space_id: incident.space_id,
    clinic_equipment_id: incident.clinic_equipment_id,
    clinical_session_id: incident.clinical_session_id,
    type: incident.type,
    severity: incident.severity,
    status: incident.status,
    title: incident.title,
    description: incident.description,
    occurred_at: toDateTimeInput(incident.occurred_at),
    resolution: incident.resolution ?? '',
});

const submit = (): void => {
    form.put(admin.clinicIncidents.update(incident.id).url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Incidencias', href: admin.clinicIncidents.index() },
            { title: 'Editar', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar incidencia" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Editar incidencia"
            :description="incident.title"
            section="Clínicas"
            :icon="TriangleAlert"
            :action-icon="ArrowLeft"
            action-label="Volver a la incidencia"
            :action-href="admin.clinicIncidents.show(incident.id).url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <ClinicIncidentForm :form="form" v-bind="options" />

            <FormActions :cancel-href="admin.clinicIncidents.show(incident.id).url" :loading="form.processing" />
        </form>
    </div>
</template>
