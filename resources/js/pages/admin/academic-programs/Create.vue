<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, GraduationCap } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import AcademicProgramForm from '@/components/admin/forms/AcademicProgramForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import admin from '@/routes/admin';
import type { Option, TeacherOption } from '@/types/admin';
import type { AcademicProgramFormData } from '@/types/forms';

defineProps<{
    types: string[];
    durationUnits: string[];
    teacherRoles: string[];
    departments: Option[];
    specialties: Option[];
    teachers: TeacherOption[];
}>();

const form = useForm<AcademicProgramFormData>({
    department_id: null,
    specialty_id: null,
    type: 'especialidad',
    code: '',
    name: '',
    description: '',
    duration_value: null,
    duration_unit: 'semestres',
    total_periods: null,
    is_active: true,
    objectives: [],
    teachers: [],
});

const submit = (): void => {
    form.post(admin.academicPrograms.store().url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Programas académicos', href: admin.academicPrograms.index() },
            { title: 'Nuevo', href: admin.academicPrograms.create() },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo programa" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Nuevo programa académico"
            description="Registra un programa de posgrado o diplomado."
            section="Programas académicos"
            :icon="GraduationCap"
            :action-icon="ArrowLeft"
            action-label="Volver a programas"
            :action-href="admin.academicPrograms.index().url"
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

            <FormActions
                :cancel-href="admin.academicPrograms.index().url"
                submit-label="Crear programa"
                loading-text="Creando..."
                :loading="form.processing"
            />
        </form>
    </div>
</template>
