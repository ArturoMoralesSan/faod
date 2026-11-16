<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { Hash, ListTree } from 'lucide-vue-next';

import AdminCard from '@/components/admin/AdminCard.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import type { CatalogFormData } from '@/types/forms';

defineProps<{
    form: InertiaForm<CatalogFormData>;
    lockCode?: boolean;
}>();
</script>

<template>
    <AdminCard
        title="Información del catálogo"
        description="Los catálogos guardan listas simples: tipos de documento, parentescos, métodos de pago, etc."
        :icon="ListTree"
    >
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <FormInput
                id="name"
                v-model="form.name"
                label="Nombre"
                placeholder="Ej. Tipos de documento"
                :icon="ListTree"
                :error="form.errors.name"
                required
            />

            <FormInput
                id="code"
                v-model="form.code"
                label="Código"
                placeholder="Ej. document_types"
                :icon="Hash"
                :error="form.errors.code"
                :disabled="lockCode"
                help="Identificador interno: letras, números, guiones y guion bajo."
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
        </div>
    </AdminCard>
</template>
