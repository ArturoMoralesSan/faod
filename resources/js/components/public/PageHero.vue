<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';

import { home } from '@/routes';

/** Encabezado de las páginas internas del sitio público. */
defineProps<{
    title: string;
    text?: string;
    eyebrow?: string;
    crumbs?: { label: string; href?: string }[];
}>();
</script>

<template>
    <section class="relative isolate overflow-hidden bg-[#5E0F1B] text-white">
        <div class="absolute inset-0 -z-10 bg-gradient-to-br from-[#2A060C] via-[#5E0F1B] to-[#941B2B]" />
        <div class="pointer-events-none absolute -right-32 -top-32 -z-10 size-[420px] rounded-full border border-white/10" />
        <div class="pointer-events-none absolute -right-12 -top-12 -z-10 size-[260px] rounded-full border border-white/10" />

        <div class="mx-auto max-w-6xl px-4 pb-14 pt-10 sm:px-6 sm:pb-16">
            <nav aria-label="Ruta" class="flex flex-wrap items-center gap-1 text-xs text-white/65">
                <Link :href="home().url" class="hover:text-white">Inicio</Link>
                <template v-for="crumb in crumbs ?? []" :key="crumb.label">
                    <ChevronRight class="size-3.5" />
                    <Link v-if="crumb.href" :href="crumb.href" class="hover:text-white">{{ crumb.label }}</Link>
                    <span v-else class="text-white/90">{{ crumb.label }}</span>
                </template>
            </nav>
            <p v-if="eyebrow" class="mt-8 text-xs font-bold uppercase tracking-[0.16em] text-[#F6D6DB]">{{ eyebrow }}</p>
            <h1 :class="['font-[\'Manrope\',sans-serif] text-3xl font-extrabold leading-tight tracking-tight sm:text-5xl', eyebrow ? 'mt-2' : 'mt-8']">{{ title }}</h1>
            <p v-if="text" class="mt-4 max-w-2xl text-base leading-7 text-white/80 sm:text-lg">{{ text }}</p>
            <slot />
        </div>
    </section>
</template>
