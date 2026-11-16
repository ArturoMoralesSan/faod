<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { Library, Tag } from 'lucide-vue-next';

import ActiveSwitch from '@/components/admin/ActiveSwitch.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import { recordOptions } from '@/lib/admin';
import type { Option } from '@/types/admin';
import type { StudyPlanFormData } from '@/types/forms';

defineProps<{
    form: InertiaForm<StudyPlanFormData>;
    programs: Option[];
}>();
</script>

<template>
    <AdminCard
        title="Información del plan"
        description="Después de crearlo podrás organizar módulos y asignaturas por semestre."
        :icon="Library"
    >
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <FormSelect
                id="academic_program_id"
                v-model="form.academic_program_id"
                label="Programa académico"
                :options="recordOptions(programs, true)"
                :error="form.errors.academic_program_id"
                required
            />

            <FormInput
                id="name"
                v-model="form.name"
                label="Nombre del plan"
                placeholder="Ej. Plan de estudios 2026"
                :icon="Library"
                :error="form.errors.name"
                required
            />

            <FormInput
                id="version"
                v-model="form.version"
                label="Versión"
                placeholder="Ej. 2026"
                :icon="Tag"
                :error="form.errors.version"
                required
            />

            <FormInput
                id="valid_from"
                v-model="form.valid_from"
                type="date"
                label="Vigente desde"
                :error="form.errors.valid_from"
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
                <ActiveSwitch v-model="form.is_active" label="Plan vigente" :error="form.errors.is_active" />
            </div>
        </div>
    </AdminCard>
</template>
