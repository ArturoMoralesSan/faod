<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { Award, CalendarDays, ClipboardCheck, Presentation } from 'lucide-vue-next';
import { computed } from 'vue';

import ActiveSwitch from '@/components/admin/ActiveSwitch.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import { enumOptions, fullName, recordOptions } from '@/lib/admin';
import type { SelectOption } from '@/types/admin';
import type { ActivityFormData, ActivityFormOptions } from '@/types/forms';

const props = defineProps<{
    form: InertiaForm<ActivityFormData>;
} & ActivityFormOptions>();

const teacherOptions = computed<SelectOption[]>(() => props.teachers.map((t) => ({ value: t.id, label: fullName(t.people) })));
</script>

<template>
    <AdminCard title="Actividad" :icon="Presentation">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <FormInput id="title" v-model="form.title" label="Título" placeholder="Ej. Curso de actualización en endodoncia" :error="form.errors.title" required />
            </div>
            <FormSelect id="type" v-model="form.type" label="Tipo" :options="enumOptions(types)" :error="form.errors.type" required />
            <FormSelect id="status" v-model="form.status" label="Estado" :options="enumOptions(statuses)" :error="form.errors.status" required />
            <FormSelect id="coordinator_id" v-model="form.coordinator_id" label="Coordinador" placeholder="Sin asignar" :options="teacherOptions" :error="form.errors.coordinator_id" nullable />
            <FormInput id="hours" v-model="form.hours" type="number" min="1" label="Horas" :error="form.errors.hours" />
            <div class="md:col-span-2">
                <FormTextarea id="description" v-model="form.description" label="Descripción / temario" :rows="4" :error="form.errors.description" />
            </div>
            <div class="md:col-span-2">
                <FormTextarea id="objectives" v-model="form.objectives" label="Objetivos" placeholder="Qué aprenderán o lograrán los participantes." :rows="3" :error="form.errors.objectives" />
            </div>
        </div>
    </AdminCard>

    <AdminCard title="Fechas y lugar" :icon="CalendarDays">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <FormInput id="starts_at" v-model="form.starts_at" type="date" label="Inicio" :error="form.errors.starts_at" required />
            <FormInput id="ends_at" v-model="form.ends_at" type="date" label="Fin" :error="form.errors.ends_at" />
            <FormInput id="schedule" v-model="form.schedule" label="Horario" placeholder="Ej. Sábados 9:00 a 14:00" :error="form.errors.schedule" />
            <FormSelect id="modality" v-model="form.modality" label="Modalidad" :options="enumOptions(modalities)" :error="form.errors.modality" required />
            <FormSelect id="space_id" v-model="form.space_id" label="Aula o auditorio" placeholder="Fuera de la facultad / en línea" :options="recordOptions(spaces, true)" :error="form.errors.space_id" nullable />
            <FormInput id="location" v-model="form.location" label="Lugar o enlace" :error="form.errors.location" />
            <FormInput id="capacity" v-model="form.capacity" type="number" min="1" label="Cupo" help="Vacío = sin límite." :error="form.errors.capacity" />
            <FormInput id="cost" v-model="form.cost" type="number" step="0.01" min="0" label="Costo de inscripción" :error="form.errors.cost" />
        </div>
    </AdminCard>

    <AdminCard title="Constancia" :icon="Award">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <ActiveSwitch v-model="form.issues_certificate" label="Otorga constancia" />
            </div>
            <FormInput v-if="form.issues_certificate" id="min_attendance" v-model="form.min_attendance" type="number" min="0" max="100" label="Asistencia mínima (%)" :error="form.errors.min_attendance" required />
        </div>
    </AdminCard>

    <AdminCard title="Evaluación y resultados" description="Se llena al concluir la actividad." :icon="ClipboardCheck">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <FormInput
                id="evaluation_score"
                v-model="form.evaluation_score"
                type="number"
                step="0.1"
                min="0"
                max="10"
                label="Evaluación interna (0 a 10)"
                help="Calificación que la coordinación da a la actividad."
                :error="form.errors.evaluation_score"
            />
            <div class="md:col-span-2">
                <FormTextarea id="evaluation_notes" v-model="form.evaluation_notes" label="Comentarios de la evaluación" placeholder="Fortalezas, áreas de mejora, opinión de los participantes." :rows="3" :error="form.errors.evaluation_notes" />
            </div>
            <div class="md:col-span-2">
                <FormTextarea id="results" v-model="form.results" label="Resultados y observaciones" placeholder="Asistencia final, productos obtenidos, acuerdos, incidencias." :rows="3" :error="form.errors.results" />
            </div>
        </div>
    </AdminCard>
</template>
