<script setup lang="ts">
import { ShieldCheck } from 'lucide-vue-next';

import { label } from '@/lib/admin';

interface Role {
    id: number;
    name: string;
}

defineProps<{
    roles: Role[];
    error?: string;
}>();

const model = defineModel<string[]>({ required: true });

const toggle = (name: string): void => {
    model.value = model.value.includes(name)
        ? model.value.filter((role) => role !== name)
        : [...model.value, name];
};
</script>

<template>
    <div class="form-field md:col-span-2">
        <span class="form-label">
            Roles del usuario
            <span class="text-red-500">*</span>
        </span>

        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            <label
                v-for="role in roles"
                :key="role.id"
                class="flex cursor-pointer items-center gap-3 rounded-xl border border-[#E3E5E8] bg-white px-4 py-3 transition hover:border-[#E8C7CC] hover:bg-[#FAF7F7]"
                :class="{ 'border-[#E8C7CC] bg-[#FDF8F8]': model.includes(role.name) }"
            >
                <input
                    type="checkbox"
                    class="form-checkbox shrink-0"
                    :checked="model.includes(role.name)"
                    @change="toggle(role.name)"
                />

                <ShieldCheck class="h-4 w-4 shrink-0 text-[#941B2B]" />

                <span class="min-w-0 truncate text-sm font-medium text-[#292D32]">
                    {{ label(role.name) }}
                </span>
            </label>
        </div>

        <p class="form-help">
            Un usuario puede tener varios roles (por ejemplo, docente y alumno).
            Podrá cambiar entre ellos con el selector del menú lateral.
        </p>

        <p v-if="error" class="form-error">
            {{ error }}
        </p>

        <p v-if="roles.length === 0" class="form-error">
            No hay roles disponibles. Crea un rol antes de asignarlo al usuario.
        </p>
    </div>
</template>
