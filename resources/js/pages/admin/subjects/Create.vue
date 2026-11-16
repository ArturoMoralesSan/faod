<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, BookOpen } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import SubjectForm from '@/components/admin/forms/SubjectForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import admin from '@/routes/admin';
import type { SubjectFormData } from '@/types/forms';

const form = useForm<SubjectFormData>({
    code: '',
    name: '',
    description: '',
    credits: null,
    theory_hours: 0,
    practice_hours: 0,
    is_active: true,
});

const submit = (): void => {
    form.post(admin.subjects.store().url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Asignaturas', href: admin.subjects.index() },
            { title: 'Nueva', href: admin.subjects.create() },
        ],
    },
});
</script>

<template>
    <Head title="Nueva asignatura" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Nueva asignatura"
            description="Registra una asignatura o materia."
            section="Programas académicos"
            :icon="BookOpen"
            :action-icon="ArrowLeft"
            action-label="Volver a asignaturas"
            :action-href="admin.subjects.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <SubjectForm :form="form" />

            <FormActions
                :cancel-href="admin.subjects.index().url"
                submit-label="Crear asignatura"
                loading-text="Creando..."
                :loading="form.processing"
            />
        </form>
    </div>
</template>
