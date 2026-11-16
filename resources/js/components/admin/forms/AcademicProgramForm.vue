<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { GraduationCap, Hash, Plus, Target, Trash2, Users } from 'lucide-vue-next';
import { computed } from 'vue';

import ActiveSwitch from '@/components/admin/ActiveSwitch.vue';
import AdminButton from '@/components/admin/AdminButton.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import { enumOptions, fullName, recordOptions } from '@/lib/admin';
import type { Option, TeacherOption } from '@/types/admin';
import type { AcademicProgramFormData } from '@/types/forms';

const props = defineProps<{
    form: InertiaForm<AcademicProgramFormData>;
    types: string[];
    durationUnits: string[];
    teacherRoles: string[];
    departments: Option[];
    specialties: Option[];
    teachers: TeacherOption[];
}>();

const teacherOptions = computed(() =>
    props.teachers.map((teacher) => ({ value: teacher.id, label: fullName(teacher.people) })),
);

const errorFor = (key: string): string =>
    (props.form.errors as Record<string, string>)[key] ?? '';

const addObjective = (): void => {
    props.form.objectives.push({ type: 'objetivo', description: '' });
};

const addTeacher = (): void => {
    props.form.teachers.push({ teacher_profile_id: null, role: 'docente', starts_at: '', ends_at: '' });
};
</script>

<template>
    <AdminCard
        title="Información del programa"
        description="Especialidades, maestrías, doctorados y diplomados de posgrado."
        :icon="GraduationCap"
    >
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <FormInput
                id="name"
                v-model="form.name"
                label="Nombre"
                placeholder="Ej. Especialidad en Ortodoncia"
                :icon="GraduationCap"
                :error="form.errors.name"
                required
            />

            <FormInput
                id="code"
                v-model="form.code"
                label="Clave"
                placeholder="Ej. ESP-ORTO"
                :icon="Hash"
                :error="form.errors.code"
                required
            />

            <FormSelect
                id="type"
                v-model="form.type"
                label="Tipo de programa"
                :options="enumOptions(types)"
                :error="form.errors.type"
                required
            />

            <FormSelect
                id="specialty_id"
                v-model="form.specialty_id"
                label="Especialidad odontológica"
                placeholder="Sin especialidad"
                :options="recordOptions(specialties)"
                :error="form.errors.specialty_id"
                nullable
            />

            <FormSelect
                id="department_id"
                v-model="form.department_id"
                label="Departamento / coordinación"
                placeholder="Sin asignar"
                :options="recordOptions(departments)"
                :error="form.errors.department_id"
                nullable
            />

            <div class="grid grid-cols-2 gap-5">
                <FormInput
                    id="duration_value"
                    v-model="form.duration_value"
                    type="number"
                    min="1"
                    label="Duración"
                    :error="form.errors.duration_value"
                />

                <FormSelect
                    id="duration_unit"
                    v-model="form.duration_unit"
                    label="Unidad"
                    placeholder="Unidad"
                    :options="enumOptions(durationUnits)"
                    :error="form.errors.duration_unit"
                    nullable
                />
            </div>

            <FormInput
                id="total_periods"
                v-model="form.total_periods"
                type="number"
                min="1"
                label="Número de semestres / periodos"
                help="Se usa para organizar las asignaturas del plan académico."
                :error="form.errors.total_periods"
            />

            <div class="md:col-span-2">
                <FormTextarea
                    id="description"
                    v-model="form.description"
                    label="Descripción"
                    :error="form.errors.description"
                    :rows="3"
                />
            </div>

            <div class="md:col-span-2">
                <ActiveSwitch
                    v-model="form.is_active"
                    label="Programa activo"
                    description="Solo los programas activos admiten nuevas inscripciones."
                    :error="form.errors.is_active"
                />
            </div>
        </div>
    </AdminCard>

    <!-- Objetivos y competencias -->
    <AdminCard
        title="Objetivos y competencias"
        description="Objetivos, competencias y perfiles de ingreso y egreso del programa."
        :icon="Target"
    >
        <div class="flex flex-col gap-4">
            <div
                v-for="(objective, index) in form.objectives"
                :key="index"
                class="grid grid-cols-1 gap-4 rounded-xl border border-[#E3E5E8] p-4 md:grid-cols-[220px_1fr_auto]"
            >
                <FormSelect
                    :id="`objective_type_${index}`"
                    v-model="objective.type"
                    label="Tipo"
                    :options="enumOptions(['objetivo', 'competencia', 'perfil_ingreso', 'perfil_egreso'])"
                    :error="errorFor(`objectives.${index}.type`)"
                    required
                />

                <FormTextarea
                    :id="`objective_description_${index}`"
                    v-model="objective.description"
                    label="Descripción"
                    :rows="2"
                    :error="errorFor(`objectives.${index}.description`)"
                    required
                />

                <button
                    type="button"
                    class="mt-7 inline-flex h-10 w-10 items-center justify-center self-start rounded-lg text-[#941B2B] transition-colors hover:bg-[#F9ECEE]"
                    title="Quitar"
                    @click="form.objectives.splice(index, 1)"
                >
                    <Trash2 class="h-4 w-4" />
                </button>
            </div>

            <p v-if="!form.objectives.length" class="text-sm italic text-[#858B93]">
                Sin objetivos registrados.
            </p>

            <div>
                <AdminButton variant="secondary" :icon="Plus" @click="addObjective">
                    Agregar objetivo o competencia
                </AdminButton>
            </div>
        </div>
    </AdminCard>

    <!-- Docentes -->
    <AdminCard
        title="Coordinadores y docentes"
        description="Asigna al coordinador académico y a los docentes del programa."
        :icon="Users"
    >
        <div class="flex flex-col gap-4">
            <div
                v-for="(teacher, index) in form.teachers"
                :key="index"
                class="grid grid-cols-1 gap-4 rounded-xl border border-[#E3E5E8] p-4 md:grid-cols-[1fr_180px_160px_160px_auto]"
            >
                <FormSelect
                    :id="`teacher_${index}`"
                    v-model="teacher.teacher_profile_id"
                    label="Docente"
                    :options="teacherOptions"
                    :error="errorFor(`teachers.${index}.teacher_profile_id`)"
                    required
                />

                <FormSelect
                    :id="`teacher_role_${index}`"
                    v-model="teacher.role"
                    label="Función"
                    :options="enumOptions(teacherRoles)"
                    :error="errorFor(`teachers.${index}.role`)"
                    required
                />

                <FormInput
                    :id="`teacher_starts_${index}`"
                    v-model="teacher.starts_at"
                    type="date"
                    label="Desde"
                    :error="errorFor(`teachers.${index}.starts_at`)"
                />

                <FormInput
                    :id="`teacher_ends_${index}`"
                    v-model="teacher.ends_at"
                    type="date"
                    label="Hasta"
                    :error="errorFor(`teachers.${index}.ends_at`)"
                />

                <button
                    type="button"
                    class="mt-7 inline-flex h-10 w-10 items-center justify-center self-start rounded-lg text-[#941B2B] transition-colors hover:bg-[#F9ECEE]"
                    title="Quitar"
                    @click="form.teachers.splice(index, 1)"
                >
                    <Trash2 class="h-4 w-4" />
                </button>
            </div>

            <p v-if="!form.teachers.length" class="text-sm italic text-[#858B93]">
                Sin docentes asignados.
            </p>

            <div>
                <AdminButton variant="secondary" :icon="Plus" :disabled="!teachers.length" @click="addTeacher">
                    Asignar docente
                </AdminButton>

                <p v-if="!teachers.length" class="form-help">
                    No hay docentes activos. Regístralos desde Personas.
                </p>
            </div>
        </div>
    </AdminCard>
</template>
