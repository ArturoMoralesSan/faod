<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, BookOpen } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import SubjectForm from '@/components/admin/forms/SubjectForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import admin from '@/routes/admin';
import type { SubjectFormData } from '@/types/forms';

const props = defineProps<{
    subject: SubjectFormData & { id: number };
}>();

const form = useForm<SubjectFormData>({
    code: props.subject.code,
    name: props.subject.name,
    description: props.subject.description ?? '',
    credits: props.subject.credits,
    theory_hours: props.subject.theory_hours,
    practice_hours: props.subject.practice_hours,
    is_active: props.subject.is_active,
});

const submit = (): void => {
    form.put(admin.subjects.update(props.subject.id).url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Asignaturas', href: admin.subjects.index() },
            { title: 'Editar', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar asignatura" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Editar asignatura"
            :description="subject.name"
            section="Programas académicos"
            :icon="BookOpen"
            :action-icon="ArrowLeft"
            action-label="Volver a asignaturas"
            :action-href="admin.subjects.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <SubjectForm :form="form" />

            <FormActions :cancel-href="admin.subjects.index().url" :loading="form.processing" />
        </form>
    </div>
</template>
