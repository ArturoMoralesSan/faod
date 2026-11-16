<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CalendarClock } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import AcademicGroupForm from '@/components/admin/forms/AcademicGroupForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import admin from '@/routes/admin';
import type { StudentOption } from '@/types/admin';
import type { AcademicGroupFormData } from '@/types/forms';

const props = defineProps<{
    periods: { id: number; name: string; is_current: boolean }[];
    cohorts: { id: number; name: string; academic_program: { id: number; code: string } | null }[];
    students: StudentOption[];
}>();

const form = useForm<AcademicGroupFormData>({
    cohort_id: null,
    academic_period_id: props.periods.find((period) => period.is_current)?.id ?? null,
    period_number: 1,
    code: '',
    capacity: null,
    student_ids: [],
});

const submit = (): void => {
    form.post(admin.groups.store().url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Grupos', href: admin.groups.index() },
            { title: 'Nuevo', href: admin.groups.create() },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo grupo" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Nuevo grupo"
            description="Después de crearlo podrás asignar materias, docentes y horarios."
            section="Grupos y horarios"
            :icon="CalendarClock"
            :action-icon="ArrowLeft"
            action-label="Volver a grupos"
            :action-href="admin.groups.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <AcademicGroupForm :form="form" :periods="periods" :cohorts="cohorts" :students="students" />

            <FormActions
                :cancel-href="admin.groups.index().url"
                submit-label="Crear grupo"
                loading-text="Creando..."
                :loading="form.processing"
            />
        </form>
    </div>
</template>
