<script setup lang="ts">
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import type { Component } from 'vue';

import AdminButton from '@/components/admin/AdminButton.vue';
import AdminCard from '@/components/admin/AdminCard.vue';

/**
 * Lista de registros del expediente docente (grados, certificaciones, experiencia).
 */
defineProps<{
    title: string;
    icon: Component;
    items: { id: number; title: string; subtitle?: string; meta?: string }[];
    emptyText: string;
    addLabel: string;
    /** Solo consulta: sin agregar, editar ni eliminar. */
    readonly?: boolean;
}>();

const emit = defineEmits<{
    add: [];
    edit: [id: number];
    remove: [id: number];
}>();
</script>

<template>
    <AdminCard :title="title" :icon="icon">
        <ul v-if="items.length" class="flex flex-col gap-3">
            <li
                v-for="item in items"
                :key="item.id"
                class="flex items-start justify-between gap-3 rounded-xl border border-[#E3E5E8] p-3"
            >
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-[#292D32]">{{ item.title }}</p>
                    <p v-if="item.subtitle" class="text-xs text-[#525960]">{{ item.subtitle }}</p>
                    <p v-if="item.meta" class="text-xs text-[#858B93]">{{ item.meta }}</p>
                </div>

                <div v-if="!readonly" class="flex shrink-0 gap-1">
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#383D43] hover:bg-[#F1F2F4] hover:text-[#292D32]"
                        title="Editar"
                        @click="emit('edit', item.id)"
                    >
                        <Pencil class="h-4 w-4" />
                    </button>

                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F1F2F4]"
                        title="Eliminar"
                        @click="emit('remove', item.id)"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>
            </li>
        </ul>

        <p v-else class="text-sm italic text-[#858B93]">{{ emptyText }}</p>

        <template v-if="!readonly" #footer>
            <AdminButton variant="secondary" :icon="Plus" @click="emit('add')">{{ addLabel }}</AdminButton>
        </template>
    </AdminCard>
</template>
