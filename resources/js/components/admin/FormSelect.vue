<script setup lang="ts">
import { ChevronDown } from 'lucide-vue-next';

import type { SelectOption } from '@/types/admin';

withDefaults(
    defineProps<{
        id: string;
        label: string;
        options: SelectOption[];
        placeholder?: string;
        error?: string;
        help?: string;
        required?: boolean;
        disabled?: boolean;
        /** Permite elegir la opción vacía (campo opcional). */
        nullable?: boolean;
    }>(),
    {
        placeholder: 'Selecciona una opción',
        error: '',
        help: '',
        required: false,
        disabled: false,
        nullable: false,
    },
);

const model = defineModel<string | number | null>();
</script>

<template>
    <div class="form-field">
        <label :for="id" class="form-label">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

        <div class="form-select-wrapper relative">
            <select
                :id="id"
                v-model="model"
                :required="required"
                :disabled="disabled"
                :class="['form-select', error && 'form-select-error']"
            >
                <option :value="nullable ? null : ''" :disabled="!nullable">
                    {{ placeholder }}
                </option>

                <option
                    v-for="option in options"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>

            <ChevronDown
                class="form-select-icon pointer-events-none absolute right-4 top-1/2 h-4 w-4 -translate-y-1/2 text-[#737982]"
            />
        </div>

        <p v-if="help" class="form-help">
            {{ help }}
        </p>

        <p v-if="error" class="form-error">
            {{ error }}
        </p>
    </div>
</template>
