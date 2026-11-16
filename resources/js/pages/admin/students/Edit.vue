<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, GraduationCap } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import StudentEnrollmentForm from '@/components/admin/forms/StudentEnrollmentForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { toDateInput } from '@/lib/admin';
import admin from '@/routes/admin';
import type { StudentOption } from '@/types/admin';
import type { StudentEnrollmentFormData } from '@/types/forms';

const props = defineProps<{
    enrollment: Omit<StudentEnrollmentFormData, 'status_reason'> & { id: number };
    statuses: string[];
    programs: {
        id: number;
        name: string;
        code: string;
        cohorts: { id: number; name: string }[];
        study_plans: { id: number; name: string; version: string }[];
    }[];
    students: StudentOption[];
}>();

const form = useForm<StudentEnrollmentFormData>({
    student_profile_id: props.enrollment.student_profile_id,
    academic_program_id: props.enrollment.academic_program_id,
    cohort_id: props.enrollment.cohort_id,
    study_plan_id: props.enrollment.study_plan_id,
    status: props.enrollment.status,
    started_at: toDateInput(props.enrollment.started_at),
    withdrawn_at: toDateInput(props.enrollment.withdrawn_at),
    withdrawal_reason: props.enrollment.withdrawal_reason ?? '',
    graduated_at: toDateInput(props.enrollment.graduated_at),
    notes: props.enrollment.notes ?? '',
    status_reason: '',
});

const submit = (): void => {
    form.put(admin.students.update(props.enrollment.id).url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Alumnos', href: admin.students.index() },
            { title: 'Editar', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar inscripción" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Editar inscripción"
            description="Actualiza el programa, la generación o la situación interna del alumno."
            section="Alumnos"
            :icon="GraduationCap"
            :action-icon="ArrowLeft"
            action-label="Volver al alumno"
            :action-href="admin.students.show(enrollment.id).url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <StudentEnrollmentForm
                :form="form"
                :statuses="statuses"
                :programs="programs"
                :students="students"
                :original-status="enrollment.status"
                lock-student
            />

            <FormActions :cancel-href="admin.students.show(enrollment.id).url" :loading="form.processing" />
        </form>
    </div>
</template>
