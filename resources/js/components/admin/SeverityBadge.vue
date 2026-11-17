<script setup lang="ts">
import { computed } from 'vue';

import { label } from '@/lib/admin';

/**
 * Gravedad de una incidencia: baja | media | alta | critica.
 */
const props = defineProps<{
    value: string;
}>();

const TONES: Record<string, string> = {
    baja: 'border-[#E3E5E8] bg-[#F7F8FA] text-[#525960]',
    media: 'border-[#F1DDB0] bg-[#FDF6E7] text-[#8A5A00]',
    alta: 'border-[#F5C9A8] bg-[#FDEFE4] text-[#A64B00]',
    critica: 'border-[#941B2B] bg-[#941B2B] text-white',
};

const tone = computed(() => TONES[props.value] ?? TONES.baja);
</script>

<template>
    <span
        :class="[
            'inline-flex items-center whitespace-nowrap rounded-full border px-2.5 py-1 text-xs font-semibold',
            tone,
        ]"
    >
        {{ label(value) }}
    </span>
</template>
