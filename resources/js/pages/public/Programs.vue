<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { GraduationCap, Mail } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import PageHero from '@/components/public/PageHero.vue';
import ProgramCard from '@/components/public/ProgramCard.vue';
import { INSTITUTION } from '@/lib/institution';
import { LEVEL_TITLES } from '@/lib/public-site';
import type { LevelGroup } from '@/lib/public-site';

const props = defineProps<{
    levels: LevelGroup[];
    level: string | null;
}>();

const selected = ref<string | null>(props.level);
const visible = computed(() => props.levels.filter((group) => !selected.value || group.level === selected.value));
const total = computed(() => props.levels.reduce((sum, group) => sum + group.programs.length, 0));
</script>

<template>
    <Head title="Oferta académica" />

    <PageHero
        eyebrow="Oferta académica"
        title="Licenciatura, especialidades y posgrados"
        text="Conoce los programas de la Facultad de Odontología: su duración, objetivos, perfil de egreso y plan de estudios."
        :crumbs="[{ label: 'Oferta académica' }]"
    >
        <div v-if="levels.length" class="mt-8 flex flex-wrap gap-2" role="tablist" aria-label="Niveles">
            <button
                type="button"
                role="tab"
                :aria-selected="!selected"
                :class="['rounded-full border px-4 py-2 text-sm font-semibold transition-colors', !selected ? 'border-white bg-white text-[#941B2B]' : 'border-white/30 text-white hover:bg-white/10']"
                @click="selected = null"
            >
                Todos <span class="ml-1 text-xs opacity-70">{{ total }}</span>
            </button>
            <button
                v-for="group in levels"
                :key="group.level"
                type="button"
                role="tab"
                :aria-selected="selected === group.level"
                :class="['rounded-full border px-4 py-2 text-sm font-semibold transition-colors', selected === group.level ? 'border-white bg-white text-[#941B2B]' : 'border-white/30 text-white hover:bg-white/10']"
                @click="selected = group.level"
            >
                {{ LEVEL_TITLES[group.level]?.title ?? group.level }} <span class="ml-1 text-xs opacity-70">{{ group.programs.length }}</span>
            </button>
        </div>
    </PageHero>

    <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <section v-for="group in visible" :key="group.level" class="mb-14 last:mb-0">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-2 border-b border-[#E3E5E8] pb-4">
                <div>
                    <h2 class="font-['Manrope',sans-serif] text-2xl font-extrabold text-[#1F2226]">{{ LEVEL_TITLES[group.level]?.title ?? group.level }}</h2>
                    <p class="mt-1 text-sm text-[#737982]">{{ LEVEL_TITLES[group.level]?.text }}</p>
                </div>
                <span class="text-sm font-semibold text-[#941B2B]">{{ group.programs.length }} programa{{ group.programs.length === 1 ? '' : 's' }}</span>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <ProgramCard v-for="program in group.programs" :key="program.code" :program="program" />
            </div>
        </section>

        <div v-if="!levels.length" class="rounded-3xl border border-dashed border-[#E8C7CC] bg-white px-6 py-16 text-center">
            <GraduationCap class="mx-auto size-12 text-[#941B2B]" />
            <p class="mt-4 font-['Manrope',sans-serif] text-xl font-bold text-[#1F2226]">La oferta académica se publicará próximamente</p>
            <p class="mx-auto mt-2 max-w-md text-sm text-[#737982]">Mientras tanto, solicita informes de convocatorias y requisitos de ingreso:</p>
            <a :href="`mailto:${INSTITUTION.email}`" class="mt-5 inline-flex h-11 items-center gap-2 rounded-full bg-[#941B2B] px-5 text-sm font-semibold text-white hover:bg-[#721421]">
                <Mail class="size-4" /> {{ INSTITUTION.email }}
            </a>
        </div>
    </div>
</template>
