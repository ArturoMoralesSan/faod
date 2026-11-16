<script setup lang="ts">
import { Save } from 'lucide-vue-next';

import AdminButton from '@/components/admin/AdminButton.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

/**
 * Formulario en ventana modal para registros que se administran
 * dentro de una vista (opciones de catálogo, módulos, horarios, etc.).
 */
withDefaults(
    defineProps<{
        title: string;
        description?: string;
        loading?: boolean;
        submitLabel?: string;
    }>(),
    {
        description: '',
        loading: false,
        submitLabel: 'Guardar',
    },
);

const open = defineModel<boolean>('open', { required: true });

const emit = defineEmits<{
    submit: [];
}>();
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-h-[90vh] overflow-y-auto font-['DM_Sans',sans-serif] sm:max-w-xl">
            <DialogHeader>
                <DialogTitle
                    class="font-['Manrope',sans-serif] text-base font-semibold text-[#941B2B]"
                >
                    {{ title }}
                </DialogTitle>

                <DialogDescription v-if="description" class="text-sm text-[#737982]">
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <form class="flex flex-col gap-5" @submit.prevent="emit('submit')">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <slot />
                </div>

                <DialogFooter class="gap-3">
                    <AdminButton variant="secondary" @click="open = false">
                        Cancelar
                    </AdminButton>

                    <AdminButton
                        type="submit"
                        variant="primary"
                        :icon="Save"
                        :loading="loading"
                        loading-text="Guardando..."
                    >
                        {{ submitLabel }}
                    </AdminButton>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
