<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, DoorOpen } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import SpaceForm from '@/components/admin/forms/SpaceForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import admin from '@/routes/admin';
import type { Option } from '@/types/admin';
import type { SpaceFormData } from '@/types/forms';

const props = defineProps<{
    space: SpaceFormData & { id: number };
    types: string[];
    clinics: Option[];
    areas: (Option & { clinic_id: number })[];
}>();

const form = useForm<SpaceFormData>({
    clinic_id: props.space.clinic_id,
    clinic_area_id: props.space.clinic_area_id,
    type: props.space.type,
    code: props.space.code,
    name: props.space.name,
    building: props.space.building ?? '',
    floor: props.space.floor ?? '',
    capacity: props.space.capacity,
    is_active: props.space.is_active,
});

const submit = (): void => {
    form.put(admin.spaces.update(props.space.id).url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Espacios', href: admin.spaces.index() },
            { title: 'Editar', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar espacio" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Editar espacio"
            :description="space.name"
            section="Clínicas y espacios"
            :icon="DoorOpen"
            :action-icon="ArrowLeft"
            action-label="Volver a espacios"
            :action-href="admin.spaces.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <SpaceForm :form="form" :types="types" :clinics="clinics" :areas="areas" />

            <FormActions :cancel-href="admin.spaces.index().url" :loading="form.processing" />
        </form>
    </div>
</template>
