<script setup lang="ts">
import { ArrowLeft, Save } from 'lucide-vue-next';

import AdminButton from '@/components/admin/AdminButton.vue';
import CancelButton from '@/components/admin/CancelButton.vue';

withDefaults(
    defineProps<{
        cancelHref: string;
        submitLabel?: string;
        loading?: boolean;
        loadingText?: string;
        disabled?: boolean;
    }>(),
    {
        submitLabel: 'Guardar cambios',
        loading: false,
        loadingText: 'Guardando...',
        disabled: false,
    },
);
</script>

<template>
    <div
        class="flex flex-col-reverse gap-3 border-t border-[#DCE7DF] pt-5 sm:flex-row sm:items-center sm:justify-end"
    >
        <CancelButton :href="cancelHref" :icon="ArrowLeft" label="Cancelar" />

        <AdminButton
            type="submit"
            variant="primary"
            :icon="Save"
            :loading="loading"
            :loading-text="loadingText"
            :disabled="disabled"
        >
            {{ submitLabel }}
        </AdminButton>
    </div>
</template>
