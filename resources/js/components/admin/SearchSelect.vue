<script setup lang="ts">
import { useDebounceFn } from '@vueuse/core';
import { Search, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

import type { SearchResult } from '@/types/admin';

/**
 * Selector con búsqueda en el servidor (para listas grandes como pacientes).
 * El endpoint recibe ?q= y responde [{ id, label, hint? }].
 */
const props = withDefaults(
    defineProps<{
        id: string;
        label: string;
        url: string;
        /** Texto del valor ya elegido (al editar). */
        initialLabel?: string | null;
        placeholder?: string;
        error?: string;
        required?: boolean;
        disabled?: boolean;
    }>(),
    {
        initialLabel: null,
        placeholder: 'Escribe para buscar...',
        error: '',
        required: false,
        disabled: false,
    },
);

const model = defineModel<number | null>({ required: true });

const emit = defineEmits<{
    select: [result: SearchResult | null];
}>();

const query = ref('');
const selectedLabel = ref<string | null>(props.initialLabel);
const results = ref<SearchResult[]>([]);
const open = ref(false);
const loading = ref(false);

watch(() => props.initialLabel, (value) => (selectedLabel.value = value));

const fetchResults = useDebounceFn(async (term: string) => {
    if (term.trim().length < 2) {
        results.value = [];
        loading.value = false;

        return;
    }

    try {
        const separator = props.url.includes('?') ? '&' : '?';
        const response = await fetch(`${props.url}${separator}q=${encodeURIComponent(term)}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        results.value = response.ok ? await response.json() : [];
    } finally {
        loading.value = false;
    }
}, 300);

const onInput = (): void => {
    open.value = true;
    loading.value = true;
    fetchResults(query.value);
};

const choose = (result: SearchResult): void => {
    model.value = result.id;
    selectedLabel.value = result.label;
    query.value = '';
    results.value = [];
    open.value = false;
    emit('select', result);
};

const clear = (): void => {
    model.value = null;
    selectedLabel.value = null;
    emit('select', null);
};

// Retraso para que el clic en un resultado se registre antes de cerrar.
const close = (): void => {
    setTimeout(() => (open.value = false), 150);
};
</script>

<template>
    <div class="form-field">
        <label :for="id" class="form-label">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

        <div v-if="model && selectedLabel" class="flex items-center justify-between gap-2 rounded-xl border border-[#E8C7CC] bg-[#FDF8F8] px-4 py-2.5">
            <span class="truncate text-sm font-semibold text-[#292D32]">{{ selectedLabel }}</span>
            <button v-if="!disabled" type="button" class="text-[#941B2B]" title="Cambiar" @click="clear">
                <X class="h-4 w-4" />
            </button>
        </div>

        <div v-else class="relative">
            <Search class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-[#718477]" />
            <input
                :id="id"
                v-model="query"
                type="search"
                autocomplete="off"
                :placeholder="placeholder"
                :disabled="disabled"
                :class="['form-input pl-11', error && 'form-input-error']"
                @input="onInput"
                @focus="query.length >= 2 && (open = true)"
                @blur="close"
            />

            <ul
                v-if="open && (loading || results.length || query.length >= 2)"
                class="absolute z-50 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-[#E3E5E8] bg-white py-1 shadow-lg"
            >
                <li v-if="loading" class="px-4 py-2 text-sm text-[#858B93]">Buscando...</li>
                <template v-else>
                    <li v-for="result in results" :key="result.id">
                        <button type="button" class="block w-full px-4 py-2 text-left hover:bg-[#F1F2F4]" @mousedown.prevent="choose(result)">
                            <span class="block text-sm font-semibold text-[#292D32]">{{ result.label }}</span>
                            <span v-if="result.hint" class="block text-xs text-[#858B93]">{{ result.hint }}</span>
                        </button>
                    </li>
                    <li v-if="!results.length" class="px-4 py-2 text-sm text-[#858B93]">Sin resultados.</li>
                </template>
            </ul>
        </div>

        <p v-if="error" class="form-error">{{ error }}</p>
    </div>
</template>
