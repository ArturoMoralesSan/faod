<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Component } from 'vue';

withDefaults(
    defineProps<{
        href: string;
        label?: string;
        icon?: Component;
        block?: boolean;
    }>(),
    {
        label: 'Cancelar',
        block: false,
    },
);
</script>

<template>
    <Link
        :href="href"
        :class="[
            'inline-flex min-h-12 items-center justify-center gap-[15px] rounded-full border border-[#E3E5E8] bg-white px-[23px] py-0 font-[DM_Sans,sans-serif] text-[13px] font-semibold text-[#383D43]',
            'transition-[transform,background-color,color,border-color] duration-[250ms] ease-in-out motion-reduce:transition-none',
            'hover:-translate-y-0.5 hover:border-[#B8BEC6] hover:bg-[#F1F2F4] hover:text-[#292D32]',
            'active:translate-y-0',
            'focus-visible:outline focus-visible:outline-[3px] focus-visible:outline-[#E8C7CC] focus-visible:outline-offset-2',
            block ? 'w-full' : '',
        ]"
    >
        <component
            :is="icon"
            v-if="icon"
            class="h-4 w-4 shrink-0"
            :size="16"
            :stroke-width="2"
            aria-hidden="true"
        />

        <slot>{{ label }}</slot>
    </Link>
</template>