<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { Hash, Hospital, MapPin } from 'lucide-vue-next';

import ActiveSwitch from '@/components/admin/ActiveSwitch.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import { recordOptions } from '@/lib/admin';
import type { Option } from '@/types/admin';
import type { ClinicFormData } from '@/types/forms';

defineProps<{
    form: InertiaForm<ClinicFormData>;
    departments: Option[];
}>();
</script>

<template>
    <AdminCard
        title="Información de la clínica"
        description="Después de registrarla podrás agregar sus consultorios desde Espacios."
        :icon="Hospital"
    >
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <FormInput
                id="name"
                v-model="form.name"
                label="Nombre"
                placeholder="Ej. Clínica de Ortodoncia"
                :icon="Hospital"
                :error="form.errors.name"
                required
            />

            <FormInput
                id="code"
                v-model="form.code"
                label="Clave"
                placeholder="Ej. CL-ORT"
                :icon="Hash"
                :error="form.errors.code"
                required
            />

            <FormSelect
                id="department_id"
                v-model="form.department_id"
                label="Departamento"
                placeholder="Sin asignar"
                :options="recordOptions(departments)"
                :error="form.errors.department_id"
                nullable
            />

            <FormInput
                id="location"
                v-model="form.location"
                label="Ubicación"
                placeholder="Ej. Edificio B, planta baja"
                :icon="MapPin"
                :error="form.errors.location"
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
                <ActiveSwitch v-model="form.is_active" label="Clínica activa" :error="form.errors.is_active" />
            </div>
        </div>
    </AdminCard>
</template>
