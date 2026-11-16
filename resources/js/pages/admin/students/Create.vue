<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, GraduationCap } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import StudentEnrollmentForm from '@/components/admin/forms/StudentEnrollmentForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import admin from '@/routes/admin';
import type { StudentOption } from '@/types/admin';
import type { StudentEnrollmentFormData } from '@/types/forms';

const props = defineProps<{
    statuses: string[];
    programs: {
        id: number;
        name: string;
        code: string;
        cohorts: { id: number; name: string }[];
        study_plans: { id: number; name: string; version: string }[];
    }[];
    students: StudentOption[];
    studentProfileId: number | null;
}>();

const form = useForm<StudentEnrollmentFormData>({
    student_profile_id: props.studentProfileId,
    academic_program_id: null,
    cohort_id: null,
    study_plan_id: null,
    status: 'activo',
    started_at: new Date().toISOString().substring(0, 10),
    withdrawn_at: '',
    withdrawal_reason: '',
    graduated_at: '',
    notes: '',
    status_reason: '',
});

const submit = (): void => {
    form.post(admin.students.store().url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Alumnos', href: admin.students.index() },
            { title: 'Inscribir', href: admin.students.create() },
        ],
    },
});
</script>

<template>
    <Head title="Inscribir alumno" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Inscribir alumno"
            description="Asigna un alumno a un programa, generación y plan académico."
            section="Alumnos"
            :icon="GraduationCap"
            :action-icon="ArrowLeft"
            action-label="Volver a alumnos"
            :action-href="admin.students.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <StudentEnrollmentForm :form="form" :statuses="statuses" :programs="programs" :students="students" />

            <FormActions
                :cancel-href="admin.students.index().url"
                submit-label="Inscribir alumno"
                loading-text="Inscribiendo..."
                :loading="form.processing"
            />
        </form>
    </div>
</template>
