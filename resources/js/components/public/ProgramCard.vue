<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, Clock, Layers } from 'lucide-vue-next';

import { label } from '@/lib/admin';
import { duration } from '@/lib/public-site';
import type { ProgramCard } from '@/lib/public-site';
import site from '@/routes/public';

defineProps<{ program: ProgramCard }>();
</script>

<template>
    <Link
        :href="site.programs.show(program.code).url"
        class="group flex h-full flex-col rounded-2xl border border-[#E3E5E8] bg-white p-6 transition-all duration-200 hover:-translate-y-0.5 hover:border-[#E8C7CC] hover:shadow-[0_14px_36px_-18px_rgba(148,27,43,0.35)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#941B2B] motion-reduce:transition-none motion-reduce:hover:translate-y-0"
    >
        <div class="flex items-start justify-between gap-3">
            <span class="rounded-full bg-[#F9ECEE] px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-[#941B2B]">{{ label(program.type) }}</span>
            <ArrowUpRight class="size-5 shrink-0 text-[#C9CCD1] transition-colors group-hover:text-[#941B2B]" />
        </div>
        <h3 class="mt-4 font-['Manrope',sans-serif] text-lg font-extrabold leading-snug text-[#1F2226] group-hover:text-[#941B2B]">{{ program.name }}</h3>
        <p v-if="program.description" class="mt-2 line-clamp-3 text-sm leading-6 text-[#737982]">{{ program.description }}</p>
        <div class="mt-auto flex flex-wrap gap-x-4 gap-y-1.5 pt-5 text-xs font-medium text-[#525960]">
            <span v-if="duration(program.duration_value, program.duration_unit)" class="inline-flex items-center gap-1.5">
                <Clock class="size-3.5 text-[#941B2B]" /> {{ duration(program.duration_value, program.duration_unit) }}
            </span>
            <span v-if="program.specialty" class="inline-flex items-center gap-1.5"><Layers class="size-3.5 text-[#941B2B]" /> {{ program.specialty }}</span>
        </div>
    </Link>
</template>
