<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { BookOpen, Hash } from 'lucide-vue-next';

import ActiveSwitch from '@/components/admin/ActiveSwitch.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import type { SubjectFormData } from '@/types/forms';

defineProps<{
    form: InertiaForm<SubjectFormData>;
}>();
</script>

<template>
    <AdminCard
        title="Información de la asignatura"
        description="Las asignaturas se organizan por semestre dentro de cada plan académico."
        :icon="BookOpen"
    >
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <FormInput
                id="name"
                v-model="form.name"
                label="Nombre"
                placeholder="Ej. Biomecánica ortodóncica"
                :icon="BookOpen"
                :error="form.errors.name"
                required
            />

            <FormInput
                id="code"
                v-model="form.code"
                label="Clave"
                placeholder="Ej. ORT-101"
                :icon="Hash"
                :error="form.errors.code"
                required
            />

            <FormInput
                id="credits"
                v-model="form.credits"
                type="number"
                step="0.01"
                min="0"
                label="Créditos"
                :error="form.errors.credits"
            />

            <div class="grid grid-cols-2 gap-5">
                <FormInput
                    id="theory_hours"
                    v-model="form.theory_hours"
                    type="number"
                    min="0"
                    label="Horas teoría"
                    :error="form.errors.theory_hours"
                />

                <FormInput
                    id="practice_hours"
                    v-model="form.practice_hours"
                    type="number"
                    min="0"
                    label="Horas práctica"
                    :error="form.errors.practice_hours"
                />
            </div>

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
                <ActiveSwitch v-model="form.is_active" label="Asignatura activa" :error="form.errors.is_active" />
            </div>
        </div>
    </AdminCard>
</template>
