<script setup lang="ts">
withDefaults(
    defineProps<{
        id: string;
        label: string;
        placeholder?: string;
        error?: string;
        help?: string;
        required?: boolean;
        rows?: number;
    }>(),
    {
        placeholder: '',
        error: '',
        help: '',
        required: false,
        rows: 4,
    },
);

const model = defineModel<string | null>();
</script>

<template>
    <div class="form-field">
        <label :for="id" class="form-label">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

        <textarea
            :id="id"
            v-model="model"
            :rows="rows"
            :placeholder="placeholder"
            :required="required"
            :class="['form-textarea', error && 'form-textarea-error']"
        />

        <p v-if="help" class="form-help">
            {{ help }}
        </p>

        <p v-if="error" class="form-error">
            {{ error }}
        </p>
    </div>
</template>
