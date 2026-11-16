<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Stethoscope } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import SpecialtyForm from '@/components/admin/forms/SpecialtyForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import admin from '@/routes/admin';
import type { SpecialtyFormData } from '@/types/forms';

const form = useForm<SpecialtyFormData>({
    code: '',
    name: '',
    description: '',
    is_active: true,
});

const submit = (): void => {
    form.post(admin.specialties.store().url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Especialidades', href: admin.specialties.index() },
            { title: 'Nueva', href: admin.specialties.create() },
        ],
    },
});
</script>

<template>
    <Head title="Nueva especialidad" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Nueva especialidad"
            description="Registra una especialidad odontológica."
            section="Programas académicos"
            :icon="Stethoscope"
            :action-icon="ArrowLeft"
            action-label="Volver a especialidades"
            :action-href="admin.specialties.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <SpecialtyForm :form="form" />

            <FormActions
                :cancel-href="admin.specialties.index().url"
                submit-label="Crear especialidad"
                loading-text="Creando..."
                :loading="form.processing"
            />
        </form>
    </div>
</template>
