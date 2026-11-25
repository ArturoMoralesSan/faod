<script setup lang="ts">
import { ChevronDown } from 'lucide-vue-next';

import type { SelectOption } from '@/types/admin';

defineProps<{
    options: SelectOption[];
    placeholder: string;
}>();

const model = defineModel<string>({ required: true });
</script>

<template>
    <div class="relative min-w-[160px] flex-1">
        <select
            v-model="model"
            class="h-[42px] w-full cursor-pointer appearance-none rounded-xl border border-[#E3E5E8] bg-white pl-3 pr-9 text-sm text-[#292D32] outline-none transition-colors duration-[250ms] ease-in-out hover:border-[#B8BEC6] focus:border-[#941B2B] focus:ring-2 focus:ring-[#941B2B]/10"
        >
            <option value="">{{ placeholder }}</option>

            <option
                v-for="option in options"
                :key="option.value"
                :value="String(option.value)"
            >
                {{ option.label }}
            </option>
        </select>

        <ChevronDown
            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#858B93]"
        />
    </div>
</template>
