<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    HelpCircle,
    Eye,
    Pencil,
    Trash2,
} from 'lucide-vue-next';

withDefaults(
    defineProps<{
        viewUrl?: string;
        editUrl?: string;
        questionsUrl?: string;
        deleteTitle?: string;
        deleteLabel?: string;
        itemName?: string;
        showView?: boolean;
        showEdit?: boolean;
        showQuestions?: boolean;
        showDelete?: boolean;
        loading?: boolean;
    }>(),
    {
        showView: true,
        showEdit: true,
        showQuestions: false,
        showDelete: true,
        deleteTitle: '',
        deleteLabel: 'Eliminar',
        itemName: 'registro',
        loading: false,
    },
);

const emit = defineEmits<{
    delete: [];
}>();
</script>

<template>
    <td class="px-5 py-4 sm:px-6">
        <div
            class="flex min-w-[220px] items-center justify-end gap-1.5 font-['DM_Sans',sans-serif]"
        >
            <!-- Acciones adicionales de cada vista -->
            <slot />

            <!-- Administrar preguntas -->
            <Link
                v-if="showQuestions && questionsUrl"
                :href="questionsUrl"
                title="Administrar preguntas"
                aria-label="Administrar preguntas"
                class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg px-2.5 text-xs font-semibold text-[#737982] transition-colors duration-[250ms] ease-in-out hover:bg-[#F9ECEE] hover:text-[#941B2B] focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#941B2B] focus-visible:outline-offset-2 motion-reduce:transition-none"
            >
                <HelpCircle :size="16" :stroke-width="1.8" />
                <span>Preguntas</span>
            </Link>

            <!-- Ver -->
            <Link
                v-if="showView && viewUrl"
                :href="viewUrl"
                :title="`Ver ${itemName}`"
                :aria-label="`Ver ${itemName}`"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-[#941B2B] transition-colors duration-[250ms] ease-in-out hover:bg-[#F9ECEE] hover:text-[#721421] focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#941B2B] focus-visible:outline-offset-2 motion-reduce:transition-none"
            >
                <Eye :size="17" :stroke-width="1.8" />
            </Link>

            <!-- Editar -->
            <Link
                v-if="showEdit && editUrl"
                :href="editUrl"
                :title="`Editar ${itemName}`"
                :aria-label="`Editar ${itemName}`"
                class="inline-flex h-9 items-center justify-center gap-1.5 rounded-full border border-[#E3E5E8] bg-white px-3 text-xs font-semibold text-[#383D43] transition-colors duration-[250ms] ease-in-out hover:border-[#941B2B] hover:bg-[#F9ECEE] hover:text-[#941B2B] focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#941B2B] focus-visible:outline-offset-2 motion-reduce:transition-none"
            >
                <Pencil :size="15" :stroke-width="1.8" />
                <span>Editar</span>
            </Link>

            <!-- Eliminar -->
            <button
                v-if="showDelete"
                type="button"
                :title="deleteTitle || `Eliminar ${itemName}`"
                :aria-label="deleteTitle || `Eliminar ${itemName}`"
                :disabled="loading"
                class="inline-flex h-9 items-center justify-center gap-1.5 rounded-full border border-[#E8C7CC] bg-white px-3 text-xs font-semibold text-[#941B2B] transition-colors duration-[250ms] ease-in-out hover:border-[#721421] hover:bg-[#721421] hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#941B2B] focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50 motion-reduce:transition-none"
                @click="emit('delete')"
            >
                <Trash2 :size="15" :stroke-width="1.8" />
                <span>{{ deleteLabel }}</span>
            </button>
        </div>
    </td>
</template>