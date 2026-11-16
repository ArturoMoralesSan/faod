<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Building2 } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import DepartmentForm from '@/components/admin/forms/DepartmentForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import admin from '@/routes/admin';
import type { PersonName } from '@/types/admin';
import type { DepartmentFormData } from '@/types/forms';

defineProps<{
    types: string[];
    parents: { id: number; name: string; type: string }[];
    people: PersonName[];
}>();

const form = useForm<DepartmentFormData>({
    parent_id: null,
    type: 'departamento',
    code: '',
    name: '',
    description: '',
    head_people_id: null,
    is_active: true,
});

const submit = (): void => {
    form.post(admin.departments.store().url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Departamentos', href: admin.departments.index() },
            { title: 'Nuevo', href: admin.departments.create() },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo departamento" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Nuevo departamento"
            description="Registra un departamento o coordinación."
            section="Administración"
            :icon="Building2"
            :action-icon="ArrowLeft"
            action-label="Volver a departamentos"
            :action-href="admin.departments.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <DepartmentForm :form="form" :types="types" :parents="parents" :people="people" />

            <FormActions
                :cancel-href="admin.departments.index().url"
                submit-label="Crear departamento"
                loading-text="Creando..."
                :loading="form.processing"
            />
        </form>
    </div>
</template>
