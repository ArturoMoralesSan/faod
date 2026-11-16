<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Hospital } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import ClinicForm from '@/components/admin/forms/ClinicForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import admin from '@/routes/admin';
import type { Option } from '@/types/admin';
import type { ClinicFormData } from '@/types/forms';

const props = defineProps<{
    clinic: ClinicFormData & { id: number };
    departments: Option[];
}>();

const form = useForm<ClinicFormData>({
    department_id: props.clinic.department_id,
    code: props.clinic.code,
    name: props.clinic.name,
    location: props.clinic.location ?? '',
    description: props.clinic.description ?? '',
    is_active: props.clinic.is_active,
});

const submit = (): void => {
    form.put(admin.clinics.update(props.clinic.id).url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Clínicas', href: admin.clinics.index() },
            { title: 'Editar', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar clínica" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Editar clínica"
            :description="clinic.name"
            section="Clínicas"
            :icon="Hospital"
            :action-icon="ArrowLeft"
            action-label="Volver a la clínica"
            :action-href="admin.clinics.show(clinic.id).url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <ClinicForm :form="form" :departments="departments" />

            <FormActions :cancel-href="admin.clinics.show(clinic.id).url" :loading="form.processing" />
        </form>
    </div>
</template>
