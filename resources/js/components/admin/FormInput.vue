<script setup lang="ts">
import type { Component } from 'vue';

withDefaults(
    defineProps<{
        id: string;
        label: string;
        type?: string;
        placeholder?: string;
        icon?: Component;
        error?: string;
        help?: string;
        required?: boolean;
        disabled?: boolean;
        step?: string;
        min?: string | number;
        max?: string | number;
        maxlength?: number;
        autocomplete?: string;
    }>(),
    {
        type: 'text',
        placeholder: '',
        error: '',
        help: '',
        required: false,
        disabled: false,
        autocomplete: 'off',
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

        <div class="relative">
            <component
                :is="icon"
                v-if="icon"
                class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-[#718477]"
            />

            <input
                :id="id"
                v-model="model"
                :type="type"
                :placeholder="placeholder"
                :required="required"
                :disabled="disabled"
                :step="step"
                :min="min"
                :max="max"
                :maxlength="maxlength"
                :autocomplete="autocomplete"
                :class="[
                    'form-input',
                    icon && 'pl-11',
                    error && 'form-input-error',
                ]"
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
