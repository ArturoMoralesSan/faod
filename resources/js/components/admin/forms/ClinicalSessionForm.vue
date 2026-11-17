<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { CalendarCheck, NotebookPen } from 'lucide-vue-next';
import { computed, watch } from 'vue';

import AdminCard from '@/components/admin/AdminCard.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import { enumOptions, fullName, recordOptions } from '@/lib/admin';
import type { SelectOption } from '@/types/admin';
import type { ClinicalSessionFormData, ClinicalSessionFormOptions } from '@/types/forms';

const props = defineProps<{
    form: InertiaForm<ClinicalSessionFormData>;
} & ClinicalSessionFormOptions>();

const spaceOptions = computed(() =>
    recordOptions(props.spaces.filter((s) => s.clinic_id === props.form.clinic_id), true),
);

const groupOptions = computed<SelectOption[]>(() =>
    props.groups
        .filter((g) => !props.form.academic_period_id || g.academic_period_id === props.form.academic_period_id)
        .map((g) => ({ value: g.id, label: g.code })),
);

// Supervisores asignados a la clínica primero.
const supervisorOptions = computed<SelectOption[]>(() => {
    const assigned = new Set(
        props.clinicSupervisors.filter((s) => s.clinic_id === props.form.clinic_id).map((s) => s.teacher_profile_id),
    );

    return [...props.teachers]
        .sort((a, b) => Number(assigned.has(b.id)) - Number(assigned.has(a.id)))
        .map((t) => ({ value: t.id, label: `${fullName(t.people)}${assigned.has(t.id) ? ' (supervisor de la clínica)' : ''}` }));
});

watch(
    () => props.form.clinic_id,
    () => {
        if (!spaceOptions.value.some((o) => o.value === props.form.space_id)) {
            props.form.space_id = null;
        }
    },
);

watch(
    () => props.form.academic_period_id,
    () => {
        if (!groupOptions.value.some((o) => o.value === props.form.academic_group_id)) {
            props.form.academic_group_id = null;
        }
    },
);
</script>

<template>
    <AdminCard title="Datos de la sesión" description="Fecha, consultorio y supervisor de la práctica clínica." :icon="CalendarCheck">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <FormSelect id="clinic_id" v-model="form.clinic_id" label="Clínica" :options="recordOptions(clinics)" :error="form.errors.clinic_id" required />

            <FormSelect
                id="space_id"
                v-model="form.space_id"
                label="Consultorio"
                placeholder="Sin consultorio específico"
                :options="spaceOptions"
                :error="form.errors.space_id"
                nullable
            />

            <FormInput id="date" v-model="form.date" type="date" label="Fecha" :error="form.errors.date" required />

            <div class="grid grid-cols-2 gap-5">
                <FormInput id="start_time" v-model="form.start_time" type="time" label="Inicio" :error="form.errors.start_time" required />
                <FormInput id="end_time" v-model="form.end_time" type="time" label="Fin" :error="form.errors.end_time" required />
            </div>

            <FormSelect
                id="supervisor_id"
                v-model="form.supervisor_id"
                label="Docente supervisor"
                placeholder="Sin asignar"
                :options="supervisorOptions"
                :error="form.errors.supervisor_id"
                nullable
            />

            <FormSelect id="status" v-model="form.status" label="Estado" :options="enumOptions(statuses)" :error="form.errors.status" required />
        </div>
    </AdminCard>

    <AdminCard title="Grupo y contenido" description="Al elegir un grupo se agregan sus alumnos a la lista de asistencia." :icon="NotebookPen">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <FormSelect
                id="academic_period_id"
                v-model="form.academic_period_id"
                label="Periodo"
                placeholder="Según la fecha"
                :options="recordOptions(periods)"
                :error="form.errors.academic_period_id"
                nullable
            />

            <FormSelect
                id="academic_group_id"
                v-model="form.academic_group_id"
                label="Grupo"
                placeholder="Sin grupo"
                :options="groupOptions"
                :error="form.errors.academic_group_id"
                nullable
            />

            <FormSelect
                id="subject_id"
                v-model="form.subject_id"
                label="Asignatura"
                placeholder="Sin asignatura"
                :options="recordOptions(subjects, true)"
                :error="form.errors.subject_id"
                nullable
            />

            <FormInput id="topic" v-model="form.topic" label="Tema o actividad" placeholder="Ej. Profilaxis y control de placa" :error="form.errors.topic" />

            <div class="md:col-span-2">
                <FormTextarea id="notes" v-model="form.notes" label="Observaciones" :rows="3" :error="form.errors.notes" />
            </div>
        </div>
    </AdminCard>
</template>
