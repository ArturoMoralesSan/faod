<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { Building, DoorOpen, Hash, Users } from 'lucide-vue-next';
import { computed, watch } from 'vue';

import ActiveSwitch from '@/components/admin/ActiveSwitch.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import { enumOptions, recordOptions } from '@/lib/admin';
import type { Option } from '@/types/admin';
import type { SpaceFormData } from '@/types/forms';

const props = defineProps<{
    form: InertiaForm<SpaceFormData>;
    types: string[];
    clinics: Option[];
    areas: (Option & { clinic_id: number })[];
}>();

const clinicAreas = computed(() => props.areas.filter((area) => area.clinic_id === props.form.clinic_id));

// El área pertenece a la clínica: al cambiar de clínica se limpia.
watch(
    () => props.form.clinic_id,
    () => {
        if (!clinicAreas.value.some((area) => area.id === props.form.clinic_area_id)) {
            props.form.clinic_area_id = null;
        }
    },
);
</script>

<template>
    <AdminCard
        title="Información del espacio"
        description="Aulas, laboratorios, consultorios y auditorios para clases, cursos y atención clínica."
        :icon="DoorOpen"
    >
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <FormInput
                id="name"
                v-model="form.name"
                label="Nombre"
                placeholder="Ej. Aula 3 / Consultorio 12"
                :icon="DoorOpen"
                :error="form.errors.name"
                required
            />

            <FormInput
                id="code"
                v-model="form.code"
                label="Clave"
                placeholder="Ej. A-03"
                :icon="Hash"
                :error="form.errors.code"
                required
            />

            <FormSelect
                id="type"
                v-model="form.type"
                label="Tipo"
                :options="enumOptions(types)"
                :error="form.errors.type"
                required
            />

            <FormSelect
                id="clinic_id"
                v-model="form.clinic_id"
                label="Clínica"
                placeholder="No pertenece a una clínica"
                :options="recordOptions(clinics)"
                :error="form.errors.clinic_id"
                help="Solo para consultorios o espacios de una clínica."
                nullable
            />

            <FormSelect
                v-if="clinicAreas.length"
                id="clinic_area_id"
                v-model="form.clinic_area_id"
                label="Área de la clínica"
                placeholder="Sin área"
                :options="recordOptions(clinicAreas)"
                :error="form.errors.clinic_area_id"
                nullable
            />

            <FormInput
                id="building"
                v-model="form.building"
                label="Edificio"
                :icon="Building"
                :error="form.errors.building"
            />

            <div class="grid grid-cols-2 gap-5">
                <FormInput id="floor" v-model="form.floor" label="Planta" :error="form.errors.floor" />

                <FormInput
                    id="capacity"
                    v-model="form.capacity"
                    type="number"
                    min="1"
                    label="Capacidad"
                    :icon="Users"
                    :error="form.errors.capacity"
                />
            </div>

            <div class="md:col-span-2">
                <ActiveSwitch v-model="form.is_active" label="Espacio disponible" :error="form.errors.is_active" />
            </div>
        </div>
    </AdminCard>
</template>
