<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { Hash, Stethoscope } from 'lucide-vue-next';

import ActiveSwitch from '@/components/admin/ActiveSwitch.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import type { SpecialtyFormData } from '@/types/forms';

defineProps<{
    form: InertiaForm<SpecialtyFormData>;
}>();
</script>

<template>
    <AdminCard
        title="Información de la especialidad"
        description="Las especialidades se usan en programas, docentes, clínicas, diagnósticos y tratamientos."
        :icon="Stethoscope"
    >
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <FormInput
                id="name"
                v-model="form.name"
                label="Nombre"
                placeholder="Ej. Ortodoncia"
                :icon="Stethoscope"
                :error="form.errors.name"
                required
            />

            <FormInput
                id="code"
                v-model="form.code"
                label="Clave"
                placeholder="Ej. ORTO"
                :icon="Hash"
                :error="form.errors.code"
                :maxlength="30"
                required
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
                <ActiveSwitch v-model="form.is_active" label="Especialidad activa" :error="form.errors.is_active" />
            </div>
        </div>
    </AdminCard>
</template>
