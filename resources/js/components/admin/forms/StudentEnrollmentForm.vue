<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { GraduationCap, UserRound } from 'lucide-vue-next';
import { computed, watch } from 'vue';

import AdminCard from '@/components/admin/AdminCard.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import { enumOptions, fullName } from '@/lib/admin';
import type { StudentOption } from '@/types/admin';
import type { StudentEnrollmentFormData } from '@/types/forms';

interface Program {
    id: number;
    name: string;
    code: string;
    cohorts: { id: number; name: string }[];
    study_plans: { id: number; name: string; version: string }[];
}

const props = defineProps<{
    form: InertiaForm<StudentEnrollmentFormData>;
    statuses: string[];
    programs: Program[];
    students: StudentOption[];
    /** En edición el alumno no se cambia. */
    lockStudent?: boolean;
    /** Valor original para saber si cambió la situación. */
    originalStatus?: string;
}>();

const studentOptions = computed(() =>
    props.students.map((student) => ({
        value: student.id,
        label: `${student.registration_number} · ${fullName(student.people)}`,
    })),
);

const programOptions = computed(() =>
    props.programs.map((program) => ({ value: program.id, label: `${program.code} · ${program.name}` })),
);

const selectedProgram = computed(() =>
    props.programs.find((program) => program.id === props.form.academic_program_id),
);

const cohortOptions = computed(() =>
    (selectedProgram.value?.cohorts ?? []).map((cohort) => ({ value: cohort.id, label: cohort.name })),
);

const planOptions = computed(() =>
    (selectedProgram.value?.study_plans ?? []).map((plan) => ({
        value: plan.id,
        label: `${plan.name} (v${plan.version})`,
    })),
);

const statusChanged = computed(
    () => props.originalStatus !== undefined && props.originalStatus !== props.form.status,
);

// Al cambiar de programa, la generación y el plan dejan de ser válidos.
watch(
    () => props.form.academic_program_id,
    (current, previous) => {
        if (previous !== undefined && current !== previous) {
            props.form.cohort_id = null;
            props.form.study_plan_id = null;
        }
    },
);
</script>

<template>
    <AdminCard
        title="Inscripción interna"
        description="Control interno: no sustituye el sistema oficial de control escolar de la universidad."
        :icon="GraduationCap"
    >
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <FormSelect
                    id="student_profile_id"
                    v-model="form.student_profile_id"
                    label="Alumno"
                    :options="studentOptions"
                    :error="form.errors.student_profile_id"
                    :disabled="lockStudent"
                    help="El alumno debe estar registrado en Personas con perfil de alumno."
                    required
                />
            </div>

            <FormSelect
                id="academic_program_id"
                v-model="form.academic_program_id"
                label="Programa académico"
                :options="programOptions"
                :error="form.errors.academic_program_id"
                required
            />

            <FormSelect
                id="cohort_id"
                v-model="form.cohort_id"
                label="Generación"
                placeholder="Sin generación"
                :options="cohortOptions"
                :error="form.errors.cohort_id"
                :disabled="!selectedProgram"
                nullable
            />

            <FormSelect
                id="study_plan_id"
                v-model="form.study_plan_id"
                label="Plan académico"
                placeholder="Sin plan"
                :options="planOptions"
                :error="form.errors.study_plan_id"
                :disabled="!selectedProgram"
                nullable
            />

            <FormInput
                id="started_at"
                v-model="form.started_at"
                type="date"
                label="Fecha de ingreso"
                :error="form.errors.started_at"
            />
        </div>
    </AdminCard>

    <AdminCard
        title="Situación interna"
        description="Bajas y egresos quedan registrados en el historial del alumno."
        :icon="UserRound"
    >
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <FormSelect
                id="status"
                v-model="form.status"
                label="Situación"
                :options="enumOptions(statuses)"
                :error="form.errors.status"
                required
            />

            <FormInput
                v-if="form.status === 'egresado'"
                id="graduated_at"
                v-model="form.graduated_at"
                type="date"
                label="Fecha de egreso"
                :error="form.errors.graduated_at"
                required
            />

            <FormInput
                v-if="form.status === 'baja'"
                id="withdrawn_at"
                v-model="form.withdrawn_at"
                type="date"
                label="Fecha de baja"
                :error="form.errors.withdrawn_at"
                required
            />

            <div v-if="form.status === 'baja'" class="md:col-span-2">
                <FormTextarea
                    id="withdrawal_reason"
                    v-model="form.withdrawal_reason"
                    label="Motivo de la baja"
                    :rows="2"
                    :error="form.errors.withdrawal_reason"
                    required
                />
            </div>

            <div v-if="statusChanged" class="md:col-span-2">
                <FormTextarea
                    id="status_reason"
                    v-model="form.status_reason"
                    label="Observaciones del cambio de situación"
                    :rows="2"
                    help="Se guardará en el historial interno."
                    :error="form.errors.status_reason"
                />
            </div>

            <div class="md:col-span-2">
                <FormTextarea
                    id="notes"
                    v-model="form.notes"
                    label="Notas internas"
                    :rows="3"
                    :error="form.errors.notes"
                />
            </div>
        </div>
    </AdminCard>
</template>
