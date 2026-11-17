<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { ClipboardCheck, TriangleAlert } from 'lucide-vue-next';
import { computed, watch } from 'vue';

import AdminCard from '@/components/admin/AdminCard.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import { enumOptions, formatDate, formatTime, recordOptions } from '@/lib/admin';
import type { SelectOption } from '@/types/admin';
import type { ClinicIncidentFormData, ClinicIncidentFormOptions } from '@/types/forms';

const props = defineProps<{
    form: InertiaForm<ClinicIncidentFormData>;
} & ClinicIncidentFormOptions>();

const byClinic = <T extends { clinic_id: number }>(items: T[]) =>
    items.filter((item) => item.clinic_id === props.form.clinic_id);

const spaceOptions = computed(() => recordOptions(byClinic(props.spaces), true));
const equipmentOptions = computed(() => recordOptions(byClinic(props.equipment), true));
const sessionOptions = computed<SelectOption[]>(() =>
    byClinic(props.sessions).map((s) => ({
        value: s.id,
        label: `${formatDate(s.date)} ${formatTime(s.start_time)}${s.topic ? ` · ${s.topic}` : ''}`,
    })),
);

const isClosing = computed(() => ['resuelta', 'cerrada'].includes(props.form.status));

// Espacio, equipo y sesión pertenecen a la clínica: al cambiarla se limpian.
watch(
    () => props.form.clinic_id,
    () => {
        const keep = (options: SelectOption[], value: number | null) =>
            options.some((o) => o.value === value) ? value : null;

        props.form.space_id = keep(spaceOptions.value, props.form.space_id);
        props.form.clinic_equipment_id = keep(equipmentOptions.value, props.form.clinic_equipment_id);
        props.form.clinical_session_id = keep(sessionOptions.value, props.form.clinical_session_id);
    },
);
</script>

<template>
    <AdminCard title="Datos de la incidencia" description="Qué pasó, dónde y con qué gravedad." :icon="TriangleAlert">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <FormInput id="title" v-model="form.title" label="Título" placeholder="Ej. Fuga de agua en unidad dental 4" :error="form.errors.title" required />
            </div>

            <FormSelect id="clinic_id" v-model="form.clinic_id" label="Clínica" :options="recordOptions(clinics)" :error="form.errors.clinic_id" required />
            <FormInput id="occurred_at" v-model="form.occurred_at" type="datetime-local" label="Fecha y hora" :error="form.errors.occurred_at" required />

            <FormSelect id="type" v-model="form.type" label="Tipo" :options="enumOptions(types)" :error="form.errors.type" required />
            <FormSelect id="severity" v-model="form.severity" label="Gravedad" :options="enumOptions(severities)" :error="form.errors.severity" required />

            <FormSelect id="space_id" v-model="form.space_id" label="Consultorio o espacio" placeholder="No aplica" :options="spaceOptions" :error="form.errors.space_id" nullable />
            <FormSelect id="clinic_equipment_id" v-model="form.clinic_equipment_id" label="Equipo" placeholder="No aplica" :options="equipmentOptions" :error="form.errors.clinic_equipment_id" nullable />

            <FormSelect
                id="clinical_session_id"
                v-model="form.clinical_session_id"
                label="Sesión clínica"
                placeholder="No ocurrió en una sesión"
                :options="sessionOptions"
                :error="form.errors.clinical_session_id"
                help="Sesiones de los últimos 60 días."
                nullable
            />

            <div class="md:col-span-2">
                <FormTextarea id="description" v-model="form.description" label="Descripción" :rows="4" :error="form.errors.description" required />
            </div>
        </div>
    </AdminCard>

    <AdminCard title="Seguimiento" :icon="ClipboardCheck">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <FormSelect id="status" v-model="form.status" label="Estado" :options="enumOptions(statuses)" :error="form.errors.status" required />
            <div />

            <div class="md:col-span-2">
                <FormTextarea
                    id="resolution"
                    v-model="form.resolution"
                    label="Resolución"
                    :rows="3"
                    :error="form.errors.resolution"
                    :required="isClosing"
                    :help="isClosing ? 'Obligatoria al marcarla como resuelta o cerrada.' : ''"
                />
            </div>
        </div>
    </AdminCard>
</template>
