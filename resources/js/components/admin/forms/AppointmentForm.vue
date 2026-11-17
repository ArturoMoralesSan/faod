<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { CalendarRange, NotebookPen } from 'lucide-vue-next';
import { computed, watch } from 'vue';

import AdminCard from '@/components/admin/AdminCard.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import SearchSelect from '@/components/admin/SearchSelect.vue';
import { fullName, recordOptions } from '@/lib/admin';
import type { SearchResult, SelectOption } from '@/types/admin';
import type { AppointmentFormData, AppointmentFormOptions } from '@/types/forms';

const props = defineProps<{
    form: InertiaForm<AppointmentFormData>;
    patientLabel?: string | null;
    /** Al editar no se cambia el paciente. */
    lockPatient?: boolean;
} & AppointmentFormOptions>();

const spaceOptions = computed(() =>
    recordOptions(props.spaces.filter((s) => s.clinic_id === props.form.clinic_id), true),
);
const studentOptions = computed<SelectOption[]>(() =>
    props.students.map((s) => ({ value: s.id, label: `${s.registration_number} · ${fullName(s.people)}` })),
);
const teacherOptions = computed<SelectOption[]>(() => props.teachers.map((t) => ({ value: t.id, label: fullName(t.people) })));
const planOptions = computed<SelectOption[]>(() =>
    props.treatmentPlans.filter((p) => p.patient_id === props.form.patient_id).map((p) => ({ value: p.id, label: p.title })),
);

watch(
    () => props.form.clinic_id,
    () => {
        if (!spaceOptions.value.some((o) => o.value === props.form.space_id)) {
            props.form.space_id = null;
        }
    },
);

// Al elegir paciente: su clínica habitual y sus planes de tratamiento en curso.
const onPatient = (result: SearchResult | null): void => {
    props.form.treatment_plan_id = null;

    if (result?.clinic_id && !props.form.clinic_id) {
        props.form.clinic_id = result.clinic_id as number;
    }

    if (result) {
        router.reload({ only: ['treatmentPlans'], data: { patient_id: result.id } });
    }
};

// Duración rápida a partir de la hora de inicio.
const setDuration = (minutes: number): void => {
    const [h, m] = props.form.start_time.split(':').map(Number);
    const total = h * 60 + m + minutes;
    props.form.end_time = `${String(Math.floor(total / 60) % 24).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`;
};
</script>

<template>
    <AdminCard title="Cita" description="Paciente, lugar y horario." :icon="CalendarRange">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <SearchSelect
                    id="patient_id"
                    v-model="form.patient_id"
                    label="Paciente"
                    :url="patientSearchUrl"
                    :initial-label="patientLabel"
                    placeholder="Nombre, expediente, CURP o teléfono..."
                    :error="form.errors.patient_id"
                    :disabled="lockPatient"
                    required
                    @select="onPatient"
                />
            </div>

            <FormSelect id="clinic_id" v-model="form.clinic_id" label="Clínica" :options="recordOptions(clinics)" :error="form.errors.clinic_id" required />
            <FormSelect id="space_id" v-model="form.space_id" label="Consultorio" placeholder="Sin asignar" :options="spaceOptions" :error="form.errors.space_id" nullable />

            <FormInput id="date" v-model="form.date" type="date" label="Fecha" :error="form.errors.date" required />
            <div>
                <div class="grid grid-cols-2 gap-3">
                    <FormInput id="start_time" v-model="form.start_time" type="time" label="Inicio" :error="form.errors.start_time" required />
                    <FormInput id="end_time" v-model="form.end_time" type="time" label="Fin" :error="form.errors.end_time" required />
                </div>
                <div class="mt-2 flex gap-2 text-xs">
                    <button v-for="m in [30, 60, 90, 120]" :key="m" type="button" class="rounded-full border border-[#E3E5E8] px-2.5 py-1 font-semibold text-[#525960] hover:border-[#E8C7CC]" @click="setDuration(m)">
                        {{ m < 60 ? `${m} min` : `${m / 60} h` }}
                    </button>
                </div>
            </div>
        </div>
    </AdminCard>

    <AdminCard title="Atención" :icon="NotebookPen">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <FormSelect id="student_profile_id" v-model="form.student_profile_id" label="Alumno que atiende" placeholder="Sin asignar" :options="studentOptions" :error="form.errors.student_profile_id" nullable />
            <FormSelect id="teacher_profile_id" v-model="form.teacher_profile_id" label="Docente" placeholder="Sin asignar" :options="teacherOptions" :error="form.errors.teacher_profile_id" nullable />
            <FormSelect id="treatment_plan_id" v-model="form.treatment_plan_id" label="Plan de tratamiento" placeholder="Ninguno" :options="planOptions" :error="form.errors.treatment_plan_id" nullable />
            <FormInput id="reason" v-model="form.reason" label="Motivo" placeholder="Ej. Revisión, resina en 36" :error="form.errors.reason" />
            <div class="md:col-span-2">
                <FormTextarea id="notes" v-model="form.notes" label="Notas" :rows="2" :error="form.errors.notes" />
            </div>
        </div>
    </AdminCard>
</template>
