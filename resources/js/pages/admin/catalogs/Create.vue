<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ListTree } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import CatalogForm from '@/components/admin/forms/CatalogForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import admin from '@/routes/admin';
import type { CatalogFormData } from '@/types/forms';

const form = useForm<CatalogFormData>({
    code: '',
    name: '',
    description: '',
});

const submit = (): void => {
    form.post(admin.catalogs.store().url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Catálogos', href: admin.catalogs.index() },
            { title: 'Nuevo', href: admin.catalogs.create() },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo catálogo" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Nuevo catálogo"
            description="Después de crearlo podrás agregar sus opciones."
            section="Administración"
            :icon="ListTree"
            :action-icon="ArrowLeft"
            action-label="Volver a catálogos"
            :action-href="admin.catalogs.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <CatalogForm :form="form" />

            <FormActions
                :cancel-href="admin.catalogs.index().url"
                submit-label="Crear catálogo"
                loading-text="Creando..."
                :loading="form.processing"
            />
        </form>
    </div>
</template>
