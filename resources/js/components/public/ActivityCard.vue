<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Award, Clock, MapPin, Users } from 'lucide-vue-next';
import { computed } from 'vue';

import { formatMoney, label } from '@/lib/admin';
import { dateBadge } from '@/lib/public-site';
import type { ActivityCard } from '@/lib/public-site';
import site from '@/routes/public';

const props = defineProps<{ activity: ActivityCard; past?: boolean }>();

const badge = computed(() => dateBadge(props.activity.starts_at));
const full = computed(() => props.activity.available === 0);
</script>

<template>
    <Link
        :href="site.activities.show(activity.id).url"
        class="group flex h-full gap-4 rounded-2xl border border-[#E3E5E8] bg-white p-5 transition-all duration-200 hover:-translate-y-0.5 hover:border-[#E8C7CC] hover:shadow-[0_14px_36px_-18px_rgba(148,27,43,0.35)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#941B2B] motion-reduce:transition-none motion-reduce:hover:translate-y-0"
    >
        <div
            v-if="badge"
            :class="['flex w-16 shrink-0 flex-col items-center justify-center self-start rounded-xl py-2.5 text-center', past ? 'bg-[#F1F2F4] text-[#525960]' : 'bg-[#941B2B] text-white']"
        >
            <span class="font-['Manrope',sans-serif] text-2xl font-extrabold leading-none">{{ badge.day }}</span>
            <span class="mt-1 text-[11px] font-bold uppercase tracking-wide">{{ badge.month }}</span>
            <span :class="['text-[10px]', past ? 'text-[#858B93]' : 'text-white/75']">{{ badge.year }}</span>
        </div>

        <div class="flex min-w-0 flex-1 flex-col">
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="rounded-full bg-[#F9ECEE] px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-[#941B2B]">{{ label(activity.type) }}</span>
                <span class="rounded-full bg-[#F1F2F4] px-2 py-0.5 text-[11px] font-semibold text-[#525960]">{{ label(activity.modality) }}</span>
                <span v-if="!past && full" class="rounded-full bg-[#FDF6E7] px-2 py-0.5 text-[11px] font-semibold text-[#8A5A00]">Cupo lleno</span>
            </div>
            <h3 class="mt-2 font-['Manrope',sans-serif] text-base font-extrabold leading-snug text-[#1F2226] group-hover:text-[#941B2B]">{{ activity.title }}</h3>
            <p v-if="activity.summary" class="mt-1.5 line-clamp-2 text-sm leading-6 text-[#737982]">{{ activity.summary }}</p>

            <div class="mt-auto flex flex-wrap gap-x-4 gap-y-1.5 pt-4 text-xs font-medium text-[#525960]">
                <span v-if="activity.place" class="inline-flex items-center gap-1.5"><MapPin class="size-3.5 text-[#941B2B]" /> {{ activity.place }}</span>
                <span v-if="activity.hours" class="inline-flex items-center gap-1.5"><Clock class="size-3.5 text-[#941B2B]" /> {{ activity.hours }} h</span>
                <span v-if="!past && activity.available !== null && !full" class="inline-flex items-center gap-1.5"><Users class="size-3.5 text-[#941B2B]" /> {{ activity.available }} lugares</span>
                <span v-if="activity.issues_certificate" class="inline-flex items-center gap-1.5"><Award class="size-3.5 text-[#941B2B]" /> Con constancia</span>
                <span class="font-bold text-[#292D32]">{{ activity.cost ? formatMoney(activity.cost) : 'Sin costo' }}</span>
            </div>
        </div>
    </Link>
</template>
