<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Hospital } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import ClinicForm from '@/components/admin/forms/ClinicForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import admin from '@/routes/admin';
import type { Option } from '@/types/admin';
import type { ClinicFormData } from '@/types/forms';

defineProps<{
    departments: Option[];
}>();

const form = useForm<ClinicFormData>({
    department_id: null,
    code: '',
    name: '',
    location: '',
    description: '',
    is_active: true,
});

const submit = (): void => {
    form.post(admin.clinics.store().url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Clínicas', href: admin.clinics.index() },
            { title: 'Nueva', href: admin.clinics.create() },
        ],
    },
});
</script>

<template>
    <Head title="Nueva clínica" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Nueva clínica"
            description="Registra una clínica odontológica."
            section="Clínicas"
            :icon="Hospital"
            :action-icon="ArrowLeft"
            action-label="Volver a clínicas"
            :action-href="admin.clinics.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <ClinicForm :form="form" :departments="departments" />

            <FormActions
                :cancel-href="admin.clinics.index().url"
                submit-label="Crear clínica"
                loading-text="Creando..."
                :loading="form.processing"
            />
        </form>
    </div>
</template>
