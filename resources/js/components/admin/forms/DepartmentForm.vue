<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { Building2, Hash } from 'lucide-vue-next';
import { computed } from 'vue';

import ActiveSwitch from '@/components/admin/ActiveSwitch.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import { enumOptions, fullName } from '@/lib/admin';
import type { PersonName } from '@/types/admin';
import type { DepartmentFormData } from '@/types/forms';

const props = defineProps<{
    form: InertiaForm<DepartmentFormData>;
    types: string[];
    parents: { id: number; name: string; type: string }[];
    people: PersonName[];
}>();

const parentOptions = computed(() =>
    props.parents.map((parent) => ({ value: parent.id, label: parent.name })),
);

const peopleOptions = computed(() =>
    props.people.map((person) => ({ value: person.id, label: fullName(person) })),
);
</script>

<template>
    <AdminCard
        title="Información del departamento"
        description="Los departamentos agrupan coordinaciones, programas, clínicas y personal."
        :icon="Building2"
    >
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <FormInput
                id="name"
                v-model="form.name"
                label="Nombre"
                placeholder="Ej. Coordinación de Posgrado"
                :icon="Building2"
                :error="form.errors.name"
                required
            />

            <FormInput
                id="code"
                v-model="form.code"
                label="Clave"
                placeholder="Ej. POSG"
                :icon="Hash"
                :error="form.errors.code"
                :maxlength="30"
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
                id="parent_id"
                v-model="form.parent_id"
                label="Depende de"
                placeholder="Sin dependencia"
                :options="parentOptions"
                :error="form.errors.parent_id"
                help="Una coordinación puede depender de un departamento."
                nullable
            />

            <FormSelect
                id="head_people_id"
                v-model="form.head_people_id"
                label="Responsable"
                placeholder="Sin responsable"
                :options="peopleOptions"
                :error="form.errors.head_people_id"
                nullable
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
                <ActiveSwitch
                    v-model="form.is_active"
                    label="Departamento activo"
                    description="Los departamentos inactivos no aparecen en los formularios."
                    :error="form.errors.is_active"
                />
            </div>
        </div>
    </AdminCard>
</template>
