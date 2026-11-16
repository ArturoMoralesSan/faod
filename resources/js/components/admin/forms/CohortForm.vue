<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { UsersRound } from 'lucide-vue-next';

import AdminCard from '@/components/admin/AdminCard.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import { enumOptions, recordOptions } from '@/lib/admin';
import type { Option } from '@/types/admin';
import type { CohortFormData } from '@/types/forms';

defineProps<{
    form: InertiaForm<CohortFormData>;
    statuses: string[];
    programs: Option[];
}>();
</script>

<template>
    <AdminCard
        title="Información de la generación"
        description="Una generación agrupa a los alumnos que ingresan juntos a un programa."
        :icon="UsersRound"
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
                label="Nombre"
                placeholder="Ej. 2026-2029"
                :icon="UsersRound"
                :error="form.errors.name"
                required
            />

            <FormInput
                id="starts_at"
                v-model="form.starts_at"
                type="date"
                label="Fecha de inicio"
                :error="form.errors.starts_at"
            />

            <FormInput
                id="ends_at"
                v-model="form.ends_at"
                type="date"
                label="Fecha de término"
                :error="form.errors.ends_at"
            />

            <FormSelect
                id="status"
                v-model="form.status"
                label="Estado"
                :options="enumOptions(statuses)"
                :error="form.errors.status"
                required
            />
        </div>
    </AdminCard>
</template>
