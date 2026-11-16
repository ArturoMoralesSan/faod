<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ListTree } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import CatalogForm from '@/components/admin/forms/CatalogForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import admin from '@/routes/admin';
import type { CatalogFormData } from '@/types/forms';

const props = defineProps<{
    catalog: CatalogFormData & { id: number; is_system: boolean };
}>();

const form = useForm<CatalogFormData>({
    code: props.catalog.code,
    name: props.catalog.name,
    description: props.catalog.description ?? '',
});

const submit = (): void => {
    form.put(admin.catalogs.update(props.catalog.id).url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Catálogos', href: admin.catalogs.index() },
            { title: 'Editar', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar catálogo" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Editar catálogo"
            :description="catalog.name"
            section="Administración"
            :icon="ListTree"
            :action-icon="ArrowLeft"
            action-label="Volver al catálogo"
            :action-href="admin.catalogs.show(catalog.id).url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <CatalogForm :form="form" :lock-code="catalog.is_system" />

            <FormActions :cancel-href="admin.catalogs.show(catalog.id).url" :loading="form.processing" />
        </form>
    </div>
</template>
