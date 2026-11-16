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
    group: Omit<AcademicGroupFormData, 'student_ids'> & { id: number; students: { id: number }[] };
    periods: { id: number; name: string; is_current: boolean }[];
    cohorts: { id: number; name: string; academic_program: { id: number; code: string } | null }[];
    students: StudentOption[];
}>();

const form = useForm<AcademicGroupFormData>({
    cohort_id: props.group.cohort_id,
    academic_period_id: props.group.academic_period_id,
    period_number: props.group.period_number,
    code: props.group.code,
    capacity: props.group.capacity,
    student_ids: props.group.students.map((student) => student.id),
});

const submit = (): void => {
    form.put(admin.groups.update(props.group.id).url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Grupos', href: admin.groups.index() },
            { title: 'Editar', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar grupo" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Editar grupo"
            :description="`Grupo ${group.code}`"
            section="Grupos y horarios"
            :icon="CalendarClock"
            :action-icon="ArrowLeft"
            action-label="Volver al grupo"
            :action-href="admin.groups.show(group.id).url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <AcademicGroupForm :form="form" :periods="periods" :cohorts="cohorts" :students="students" />

            <FormActions :cancel-href="admin.groups.show(group.id).url" :loading="form.processing" />
        </form>
    </div>
</template>
