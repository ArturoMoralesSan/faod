<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Building2 } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import DepartmentForm from '@/components/admin/forms/DepartmentForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import admin from '@/routes/admin';
import type { PersonName } from '@/types/admin';
import type { DepartmentFormData } from '@/types/forms';

const props = defineProps<{
    department: DepartmentFormData & { id: number };
    types: string[];
    parents: { id: number; name: string; type: string }[];
    people: PersonName[];
}>();

const form = useForm<DepartmentFormData>({
    parent_id: props.department.parent_id,
    type: props.department.type,
    code: props.department.code ?? '',
    name: props.department.name,
    description: props.department.description ?? '',
    head_people_id: props.department.head_people_id,
    is_active: props.department.is_active,
});

const submit = (): void => {
    form.put(admin.departments.update(props.department.id).url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Departamentos', href: admin.departments.index() },
            { title: 'Editar', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar departamento" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Editar departamento"
            :description="department.name"
            section="Administración"
            :icon="Building2"
            :action-icon="ArrowLeft"
            action-label="Volver a departamentos"
            :action-href="admin.departments.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <DepartmentForm :form="form" :types="types" :parents="parents" :people="people" />

            <FormActions :cancel-href="admin.departments.index().url" :loading="form.processing" />
        </form>
    </div>
</template>
