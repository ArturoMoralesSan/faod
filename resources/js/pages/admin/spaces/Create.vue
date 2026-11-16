<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, DoorOpen } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import SpaceForm from '@/components/admin/forms/SpaceForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import admin from '@/routes/admin';
import type { Option } from '@/types/admin';
import type { SpaceFormData } from '@/types/forms';

defineProps<{
    types: string[];
    clinics: Option[];
}>();

const form = useForm<SpaceFormData>({
    clinic_id: null,
    type: 'aula',
    code: '',
    name: '',
    building: '',
    floor: '',
    capacity: null,
    is_active: true,
});

const submit = (): void => {
    form.post(admin.spaces.store().url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Espacios', href: admin.spaces.index() },
            { title: 'Nuevo', href: admin.spaces.create() },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo espacio" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Nuevo espacio"
            description="Registra un aula, laboratorio, consultorio o auditorio."
            section="Clínicas y espacios"
            :icon="DoorOpen"
            :action-icon="ArrowLeft"
            action-label="Volver a espacios"
            :action-href="admin.spaces.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <SpaceForm :form="form" :types="types" :clinics="clinics" />

            <FormActions
                :cancel-href="admin.spaces.index().url"
                submit-label="Crear espacio"
                loading-text="Creando..."
                :loading="form.processing"
            />
        </form>
    </div>
</template>
