<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Library } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import StudyPlanForm from '@/components/admin/forms/StudyPlanForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { toDateInput } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option } from '@/types/admin';
import type { StudyPlanFormData } from '@/types/forms';

const props = defineProps<{
    plan: StudyPlanFormData & { id: number };
    programs: Option[];
}>();

const form = useForm<StudyPlanFormData>({
    academic_program_id: props.plan.academic_program_id,
    name: props.plan.name,
    version: props.plan.version,
    valid_from: toDateInput(props.plan.valid_from),
    description: props.plan.description ?? '',
    is_active: props.plan.is_active,
});

const submit = (): void => {
    form.put(admin.studyPlans.update(props.plan.id).url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Planes académicos', href: admin.studyPlans.index() },
            { title: 'Editar', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar plan académico" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Editar plan académico"
            :description="plan.name"
            section="Programas académicos"
            :icon="Library"
            :action-icon="ArrowLeft"
            action-label="Volver al plan"
            :action-href="admin.studyPlans.show(plan.id).url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <StudyPlanForm :form="form" :programs="programs" />

            <FormActions :cancel-href="admin.studyPlans.show(plan.id).url" :loading="form.processing" />
        </form>
    </div>
</template>
