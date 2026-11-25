<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays } from 'lucide-vue-next';

import ActivityCard from '@/components/public/ActivityCard.vue';
import PageHero from '@/components/public/PageHero.vue';
import { label } from '@/lib/admin';
import type { ActivityCard as Activity } from '@/lib/public-site';
import site from '@/routes/public';
import type { Paginated } from '@/types/admin';

defineProps<{
    upcoming: Activity[];
    past: Paginated<Activity>;
    types: string[];
    type: string | null;
}>();

const filter = (type: string | null): void => {
    router.get(site.activities().url, type ? { tipo: type } : {}, { preserveScroll: true, preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Cursos y actividades" />

    <PageHero
        eyebrow="Educación continua"
        title="Cursos, congresos y actividades"
        text="Seminarios, talleres, diplomados, jornadas y congresos de la facultad, abiertos a alumnos, egresados y profesionales de la salud bucal."
        :crumbs="[{ label: 'Cursos y actividades' }]"
    >
        <div class="mt-8 flex flex-wrap gap-2" role="tablist" aria-label="Tipo de actividad">
            <button
                type="button"
                role="tab"
                :aria-selected="!type"
                :class="['rounded-full border px-4 py-2 text-sm font-semibold transition-colors', !type ? 'border-white bg-white text-[#941B2B]' : 'border-white/30 text-white hover:bg-white hover:text-[#292D32]']"
                @click="filter(null)"
            >
                Todas
            </button>
            <button
                v-for="item in types"
                :key="item"
                type="button"
                role="tab"
                :aria-selected="type === item"
                :class="['rounded-full border px-4 py-2 text-sm font-semibold transition-colors', type === item ? 'border-white bg-white text-[#941B2B]' : 'border-white/30 text-white hover:bg-white hover:text-[#292D32]']"
                @click="filter(item)"
            >
                {{ label(item) }}
            </button>
        </div>
    </PageHero>

    <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <section>
            <h2 class="font-['Manrope',sans-serif] text-2xl font-extrabold text-[#1F2226]">Próximas</h2>
            <div v-if="upcoming.length" class="mt-6 grid gap-4 md:grid-cols-2">
                <ActivityCard v-for="activity in upcoming" :key="activity.id" :activity="activity" />
            </div>
            <div v-else class="mt-6 rounded-3xl border border-dashed border-[#E8C7CC] bg-white px-6 py-12 text-center">
                <CalendarDays class="mx-auto size-10 text-[#941B2B]" />
                <p class="mt-3 font-['Manrope',sans-serif] text-lg font-bold text-[#1F2226]">No hay actividades programadas{{ type ? ` de tipo ${label(type).toLowerCase()}` : '' }}</p>
                <p class="mt-1 text-sm text-[#737982]">Vuelve pronto: aquí publicaremos las nuevas fechas.</p>
            </div>
        </section>

        <section v-if="past.data.length" class="mt-16">
            <h2 class="font-['Manrope',sans-serif] text-2xl font-extrabold text-[#1F2226]">Realizadas</h2>
            <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <ActivityCard v-for="activity in past.data" :key="activity.id" :activity="activity" past />
            </div>

            <nav v-if="past.last_page > 1" class="mt-8 flex flex-wrap justify-center gap-1" aria-label="Paginación">
                <template v-for="(link, index) in past.links" :key="index">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        preserve-scroll
                        :class="['inline-flex min-h-9 min-w-9 items-center justify-center rounded-lg border px-3 text-sm', link.active ? 'border-[#941B2B] bg-[#941B2B] font-semibold text-white' : 'border-[#E3E5E8] bg-white text-[#383D43] hover:border-[#B8BEC6] hover:text-[#292D32]']"
                    >
                        <span v-html="link.label" />
                    </Link>
                    <span v-else class="inline-flex min-h-9 min-w-9 items-center justify-center rounded-lg border border-[#E3E5E8] px-3 text-sm text-[#B5BAC1]"><span v-html="link.label" /></span>
                </template>
            </nav>
        </section>
    </div>
</template>
