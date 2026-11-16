<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, GraduationCap } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import AcademicProgramForm from '@/components/admin/forms/AcademicProgramForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { toDateInput } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option, TeacherOption } from '@/types/admin';
import type { AcademicProgramFormData } from '@/types/forms';

interface Program extends Omit<AcademicProgramFormData, 'objectives' | 'teachers'> {
    id: number;
    objectives: { type: string; description: string }[];
    teachers: {
        id: number;
        pivot: { role: string; starts_at: string | null; ends_at: string | null };
    }[];
}

const props = defineProps<{
    program: Program;
    types: string[];
    durationUnits: string[];
    teacherRoles: string[];
    departments: Option[];
    specialties: Option[];
    teachers: TeacherOption[];
}>();

const form = useForm<AcademicProgramFormData>({
    department_id: props.program.department_id,
    specialty_id: props.program.specialty_id,
    type: props.program.type,
    code: props.program.code,
    name: props.program.name,
    description: props.program.description ?? '',
    duration_value: props.program.duration_value,
    duration_unit: props.program.duration_unit,
    total_periods: props.program.total_periods,
    is_active: props.program.is_active,
    objectives: props.program.objectives.map(({ type, description }) => ({ type, description })),
    teachers: props.program.teachers.map((teacher) => ({
        teacher_profile_id: teacher.id,
        role: teacher.pivot.role,
        starts_at: toDateInput(teacher.pivot.starts_at),
        ends_at: toDateInput(teacher.pivot.ends_at),
    })),
});

const submit = (): void => {
    form.put(admin.academicPrograms.update(props.program.id).url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Programas académicos', href: admin.academicPrograms.index() },
            { title: 'Editar', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar programa" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Editar programa académico"
            :description="program.name"
            section="Programas académicos"
            :icon="GraduationCap"
            :action-icon="ArrowLeft"
            action-label="Volver al programa"
            :action-href="admin.academicPrograms.show(program.id).url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <AcademicProgramForm
                :form="form"
                :types="types"
                :duration-units="durationUnits"
                :teacher-roles="teacherRoles"
                :departments="departments"
                :specialties="specialties"
                :teachers="teachers"
            />

            <FormActions :cancel-href="admin.academicPrograms.show(program.id).url" :loading="form.processing" />
        </form>
    </div>
</template>
