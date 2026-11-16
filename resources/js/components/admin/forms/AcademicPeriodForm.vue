<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { CalendarDays, Hash } from 'lucide-vue-next';

import ActiveSwitch from '@/components/admin/ActiveSwitch.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import FormInput from '@/components/admin/FormInput.vue';
import type { AcademicPeriodFormData } from '@/types/forms';

defineProps<{
    form: InertiaForm<AcademicPeriodFormData>;
}>();
</script>

<template>
    <AdminCard
        title="Información del periodo"
        description="Los grupos, horarios e inscripciones ante la universidad se registran por periodo."
        :icon="CalendarDays"
    >
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <FormInput
                id="name"
                v-model="form.name"
                label="Nombre"
                placeholder="Ej. Agosto - Diciembre 2026"
                :icon="CalendarDays"
                :error="form.errors.name"
                required
            />

            <FormInput
                id="code"
                v-model="form.code"
                label="Clave"
                placeholder="Ej. 2026-B"
                :icon="Hash"
                :error="form.errors.code"
                required
            />

            <FormInput
                id="starts_at"
                v-model="form.starts_at"
                type="date"
                label="Fecha de inicio"
                :error="form.errors.starts_at"
                required
            />

            <FormInput
                id="ends_at"
                v-model="form.ends_at"
                type="date"
                label="Fecha de término"
                :error="form.errors.ends_at"
                required
            />

            <div class="md:col-span-2">
                <ActiveSwitch
                    id="is_current"
                    v-model="form.is_current"
                    label="Periodo vigente"
                    description="Solo puede haber un periodo vigente; al marcar este, se desmarca el anterior."
                    :error="form.errors.is_current"
                />
            </div>
        </div>
    </AdminCard>
</template>
