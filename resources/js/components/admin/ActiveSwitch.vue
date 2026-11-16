<script setup lang="ts">
import { Check } from 'lucide-vue-next';

withDefaults(
    defineProps<{
        id?: string;
        label?: string;
        description?: string;
        error?: string;
        disabled?: boolean;
    }>(),
    {
        id: 'is_active',
        label: 'Registro activo',
        description:
            'Determina si este registro estará disponible para su uso en el sistema.',
        error: '',
        disabled: false,
    },
);

const model = defineModel<boolean>({ required: true });
</script>

<template>
    <div class="form-field font-['DM_Sans',sans-serif]">
        <label
            class="form-label mb-2 block text-sm font-semibold text-[#292D32]"
            :for="id"
        >
            Estado
        </label>

        <label
            :for="id"
            class="flex items-center justify-between gap-4 rounded-xl border border-[#E3E5E8] bg-white p-4 transition-[border-color,background-color] duration-200 ease-in-out hover:border-[#E8C7CC] hover:bg-[#FAF7F7]"
            :class="{
                'cursor-pointer': !disabled,
                'cursor-not-allowed opacity-60': disabled,
                'border-[#E8C7CC] bg-[#FDF8F8]': model,
            }"
        >
            <div class="flex min-w-0 items-start gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]"
                >
                    <Check class="h-5 w-5" />
                </div>

                <div class="min-w-0">
                    <p class="text-sm font-semibold text-[#292D32]">
                        {{ label }}
                    </p>

                    <p class="mt-1 text-xs leading-5 text-[#737982]">
                        {{ description }}
                    </p>
                </div>
            </div>

            <input
                :id="id"
                v-model="model"
                type="checkbox"
                class="form-checkbox h-4 w-4 shrink-0 cursor-pointer rounded border-[#C9CDD2] text-[#941B2B] accent-[#941B2B] focus:ring-2 focus:ring-[#E8C7CC] focus:ring-offset-2 disabled:cursor-not-allowed"
                :disabled="disabled"
            />
        </label>

        <p v-if="error" class="form-error mt-2 text-sm text-[#941B2B]">
            {{ error }}
        </p>
    </div>
</template>