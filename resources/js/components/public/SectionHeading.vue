<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';

defineProps<{
    eyebrow: string;
    title: string;
    text?: string;
    linkHref?: string;
    linkLabel?: string;
    light?: boolean;
}>();
</script>

<template>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="max-w-2xl">
            <p :class="['text-xs font-bold uppercase tracking-[0.16em]', light ? 'text-[#F6D6DB]' : 'text-[#941B2B]']">{{ eyebrow }}</p>
            <h2 :class="['mt-2 font-[\'Manrope\',sans-serif] text-3xl font-extrabold tracking-tight sm:text-4xl', light ? 'text-white' : 'text-[#1F2226]']">{{ title }}</h2>
            <p v-if="text" :class="['mt-3 leading-7', light ? 'text-white/75' : 'text-[#737982]']">{{ text }}</p>
        </div>
        <Link
            v-if="linkHref"
            :href="linkHref"
            :class="['inline-flex shrink-0 items-center gap-1.5 text-sm font-bold hover:underline', light ? 'text-white' : 'text-[#941B2B]']"
        >
            {{ linkLabel }} <ArrowRight class="size-4" />
        </Link>
    </div>
</template>
