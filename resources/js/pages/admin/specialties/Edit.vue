<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Stethoscope } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import SpecialtyForm from '@/components/admin/forms/SpecialtyForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import admin from '@/routes/admin';
import type { SpecialtyFormData } from '@/types/forms';

const props = defineProps<{
    specialty: SpecialtyFormData & { id: number };
}>();

const form = useForm<SpecialtyFormData>({
    code: props.specialty.code,
    name: props.specialty.name,
    description: props.specialty.description ?? '',
    is_active: props.specialty.is_active,
});

const submit = (): void => {
    form.put(admin.specialties.update(props.specialty.id).url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Especialidades', href: admin.specialties.index() },
            { title: 'Editar', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar especialidad" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Editar especialidad"
            :description="specialty.name"
            section="Programas académicos"
            :icon="Stethoscope"
            :action-icon="ArrowLeft"
            action-label="Volver a especialidades"
            :action-href="admin.specialties.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <SpecialtyForm :form="form" />

            <FormActions :cancel-href="admin.specialties.index().url" :loading="form.processing" />
        </form>
    </div>
</template>
