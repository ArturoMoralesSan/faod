<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Library } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import StudyPlanForm from '@/components/admin/forms/StudyPlanForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import admin from '@/routes/admin';
import type { Option } from '@/types/admin';
import type { StudyPlanFormData } from '@/types/forms';

defineProps<{
    programs: Option[];
}>();

const form = useForm<StudyPlanFormData>({
    academic_program_id: null,
    name: '',
    version: '',
    valid_from: '',
    description: '',
    is_active: true,
});

const submit = (): void => {
    form.post(admin.studyPlans.store().url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Planes académicos', href: admin.studyPlans.index() },
            { title: 'Nuevo', href: admin.studyPlans.create() },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo plan académico" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Nuevo plan académico"
            description="Crea un plan académico interno para un programa."
            section="Programas académicos"
            :icon="Library"
            :action-icon="ArrowLeft"
            action-label="Volver a planes"
            :action-href="admin.studyPlans.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <StudyPlanForm :form="form" :programs="programs" />

            <FormActions
                :cancel-href="admin.studyPlans.index().url"
                submit-label="Crear plan"
                loading-text="Creando..."
                :loading="form.processing"
            />
        </form>
    </div>
</template>
