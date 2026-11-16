<script setup lang="ts">
import { computed } from 'vue';

import { formatTime } from '@/lib/admin';
import type { Days, ScheduleBlock } from '@/types/admin';

const props = withDefaults(
    defineProps<{
        blocks: ScheduleBlock[];
        days: Days;
        /** Mostrar sábado y domingo aunque no tengan clases. */
        showWeekend?: boolean;
        clickable?: boolean;
    }>(),
    {
        showWeekend: false,
        clickable: false,
    },
);

const emit = defineEmits<{
    select: [block: ScheduleBlock];
}>();

const PALETTE = [
    'border-[#E8C7CC] bg-[#F9ECEE] text-[#721421]',
    'border-[#BFE3CC] bg-[#E2F3E8] text-[#145F37]',
    'border-[#C7D7EE] bg-[#EAF1FB] text-[#1F4F8F]',
    'border-[#F1DDB0] bg-[#FDF6E7] text-[#7A4F00]',
    'border-[#D9CCEB] bg-[#F3EEFA] text-[#523089]',
    'border-[#C3E4E6] bg-[#E8F6F7] text-[#145E63]',
];

const visibleDays = computed(() =>
    Object.entries(props.days)
        .map(([day, name]) => ({ day: Number(day), name }))
        .filter(({ day }) => props.showWeekend || day <= 5 || props.blocks.some((b) => b.day_of_week === day)),
);

const blocksByDay = (day: number) =>
    props.blocks
        .filter((block) => block.day_of_week === day)
        .sort((a, b) => a.start_time.localeCompare(b.start_time));
</script>

<template>
    <div class="overflow-x-auto">
        <div
            class="grid min-w-[720px] gap-3"
            :style="{ gridTemplateColumns: `repeat(${visibleDays.length}, minmax(0, 1fr))` }"
        >
            <div v-for="{ day, name } in visibleDays" :key="day" class="flex flex-col gap-2">
                <p class="rounded-lg bg-[#F7F8FA] px-3 py-2 text-center text-xs font-semibold uppercase tracking-wide text-[#383D43]">
                    {{ name }}
                </p>

                <component
                    :is="clickable ? 'button' : 'div'"
                    v-for="block in blocksByDay(day)"
                    :key="block.id"
                    :type="clickable ? 'button' : undefined"
                    :class="[
                        'rounded-xl border p-3 text-left text-xs transition',
                        PALETTE[(block.colorKey ?? 0) % PALETTE.length],
                        clickable && 'cursor-pointer hover:-translate-y-0.5 hover:shadow-sm',
                    ]"
                    @click="clickable && emit('select', block)"
                >
                    <p class="font-semibold">
                        {{ formatTime(block.start_time) }} – {{ formatTime(block.end_time) }}
                    </p>
                    <p class="mt-1 text-sm font-semibold leading-tight">{{ block.title }}</p>
                    <p v-if="block.subtitle" class="mt-1 opacity-80">{{ block.subtitle }}</p>
                    <p v-if="block.meta" class="mt-0.5 opacity-70">{{ block.meta }}</p>
                </component>

                <p v-if="!blocksByDay(day).length" class="py-4 text-center text-xs italic text-[#B0B5BB]">
                    Libre
                </p>
            </div>
        </div>
    </div>
</template>
