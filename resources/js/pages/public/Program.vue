<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { BookOpen, CalendarRange, CheckCircle2, Clock, GraduationCap, Layers, Mail, Phone, Target } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import ActivityCard from '@/components/public/ActivityCard.vue';
import PageHero from '@/components/public/PageHero.vue';
import ProgramCard from '@/components/public/ProgramCard.vue';
import { label } from '@/lib/admin';
import { INSTITUTION } from '@/lib/institution';
import { duration, periodName } from '@/lib/public-site';
import type { ActivityCard as Activity, ProgramCard as Program } from '@/lib/public-site';
import site from '@/routes/public';

interface Subject {
    code: string;
    name: string;
    credits: number | null;
    theory_hours: number | null;
    practice_hours: number | null;
    required: boolean;
    module: string | null;
}

const props = defineProps<{
    program: Program & { objectives: Partial<Record<'objetivo' | 'competencia' | 'perfil_ingreso' | 'perfil_egreso', string[]>> };
    plan: {
        name: string;
        version: string;
        description: string | null;
        credits: number;
        subjects_count: number;
        periods: { period: number; subjects: Subject[] }[];
    } | null;
    related: Program[];
    activities: Activity[];
}>();

const PROFILE = [
    { key: 'objetivo', title: 'Objetivos', icon: Target },
    { key: 'competencia', title: 'Competencias', icon: CheckCircle2 },
    { key: 'perfil_ingreso', title: 'Perfil de ingreso', icon: GraduationCap },
    { key: 'perfil_egreso', title: 'Perfil de egreso', icon: GraduationCap },
] as const;

const profileSections = computed(() => PROFILE.filter((section) => props.program.objectives[section.key]?.length));

const openPeriod = ref<number | null>(props.plan?.periods[0]?.period ?? null);

const FACTS = computed(() =>
    [
        { icon: GraduationCap, label: 'Nivel', value: label(props.program.type) },
        { icon: Clock, label: 'Duración', value: duration(props.program.duration_value, props.program.duration_unit) },
        { icon: CalendarRange, label: 'Periodos', value: props.program.total_periods ? String(props.program.total_periods) : null },
        { icon: Layers, label: 'Especialidad', value: props.program.specialty },
        { icon: BookOpen, label: 'Asignaturas', value: props.plan ? String(props.plan.subjects_count) : null },
        { icon: CheckCircle2, label: 'Créditos', value: props.plan?.credits ? String(props.plan.credits) : null },
    ].filter((fact) => fact.value),
);

const hours = (s: Subject) => [s.theory_hours ? `${s.theory_hours} h teoría` : null, s.practice_hours ? `${s.practice_hours} h práctica` : null].filter(Boolean).join(' · ');
</script>

<template>
    <Head :title="program.name" />

    <PageHero
        :eyebrow="label(program.type)"
        :title="program.name"
        :crumbs="[{ label: 'Oferta académica', href: site.programs().url }, { label: program.name }]"
    >
        <dl class="mt-8 flex flex-wrap gap-3">
            <div v-for="fact in FACTS" :key="fact.label" class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur-sm">
                <dt class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-white/65"><component :is="fact.icon" class="size-3.5" /> {{ fact.label }}</dt>
                <dd class="mt-0.5 font-['Manrope',sans-serif] text-lg font-bold">{{ fact.value }}</dd>
            </div>
        </dl>
    </PageHero>

    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1fr_320px]">
        <div class="min-w-0 space-y-12">
            <!-- Presentación -->
            <section v-if="program.description">
                <h2 class="font-['Manrope',sans-serif] text-2xl font-extrabold text-[#1F2226]">Presentación</h2>
                <p class="mt-4 whitespace-pre-line text-[17px] leading-8 text-[#525960]">{{ program.description }}</p>
            </section>

            <!-- Objetivos y perfiles -->
            <section v-if="profileSections.length" class="grid gap-4 md:grid-cols-2">
                <article v-for="section in profileSections" :key="section.key" class="rounded-2xl border border-[#E3E5E8] bg-white p-6">
                    <h3 class="flex items-center gap-2 font-['Manrope',sans-serif] text-lg font-bold text-[#1F2226]">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]"><component :is="section.icon" class="size-4.5" /></span>
                        {{ section.title }}
                    </h3>
                    <ul class="mt-4 space-y-2.5">
                        <li v-for="(item, index) in program.objectives[section.key]" :key="index" class="flex gap-2.5 text-sm leading-6 text-[#525960]">
                            <CheckCircle2 class="mt-1 size-4 shrink-0 text-[#941B2B]" /> {{ item }}
                        </li>
                    </ul>
                </article>
            </section>

            <!-- Plan de estudios -->
            <section>
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h2 class="font-['Manrope',sans-serif] text-2xl font-extrabold text-[#1F2226]">Plan de estudios</h2>
                        <p v-if="plan" class="mt-1 text-sm text-[#737982]">{{ plan.name }} · versión {{ plan.version }}</p>
                    </div>
                    <p v-if="plan" class="text-sm font-semibold text-[#941B2B]">{{ plan.subjects_count }} asignaturas<template v-if="plan.credits"> · {{ plan.credits }} créditos</template></p>
                </div>
                <p v-if="plan?.description" class="mt-3 text-sm leading-6 text-[#525960]">{{ plan.description }}</p>

                <div v-if="plan && plan.periods.length" class="mt-6 space-y-3">
                    <div v-for="period in plan.periods" :key="period.period" class="overflow-hidden rounded-2xl border border-[#E3E5E8] bg-white">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left transition-colors hover:bg-[#FAF3F4]"
                            :aria-expanded="openPeriod === period.period"
                            @click="openPeriod = openPeriod === period.period ? null : period.period"
                        >
                            <span class="flex items-center gap-3">
                                <span class="flex size-9 items-center justify-center rounded-xl bg-[#941B2B] font-['Manrope',sans-serif] text-sm font-extrabold text-white">{{ period.period }}</span>
                                <span class="font-['Manrope',sans-serif] font-bold text-[#1F2226]">{{ periodName(program.duration_unit, period.period) }}</span>
                            </span>
                            <span class="text-xs font-semibold text-[#858B93]">{{ period.subjects.length }} asignatura{{ period.subjects.length === 1 ? '' : 's' }}</span>
                        </button>
                        <ul v-if="openPeriod === period.period" class="divide-y divide-[#F1F2F4] border-t border-[#E3E5E8]">
                            <li v-for="subject in period.subjects" :key="subject.code" class="flex flex-wrap items-center justify-between gap-2 px-5 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-[#292D32]">
                                        {{ subject.name }}
                                        <span v-if="!subject.required" class="ml-1 rounded-full bg-[#FDF6E7] px-2 py-0.5 text-[10px] font-bold uppercase text-[#8A5A00]">Optativa</span>
                                    </p>
                                    <p class="text-xs text-[#858B93]">{{ [subject.code, subject.module, hours(subject)].filter(Boolean).join(' · ') }}</p>
                                </div>
                                <span v-if="subject.credits" class="text-xs font-bold text-[#941B2B]">{{ subject.credits }} créditos</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <p v-else class="mt-6 rounded-2xl border border-dashed border-[#E3E5E8] bg-white px-5 py-8 text-center text-sm text-[#737982]">
                    El plan de estudios se publicará próximamente.
                </p>
            </section>

            <section v-if="activities.length">
                <h2 class="font-['Manrope',sans-serif] text-2xl font-extrabold text-[#1F2226]">Próximas actividades de la facultad</h2>
                <div class="mt-6 grid gap-4">
                    <ActivityCard v-for="activity in activities" :key="activity.id" :activity="activity" />
                </div>
            </section>
        </div>

        <!-- Lateral -->
        <aside class="space-y-5 lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl bg-[#941B2B] p-6 text-white">
                <p class="font-['Manrope',sans-serif] text-lg font-bold">¿Te interesa este programa?</p>
                <p class="mt-2 text-sm leading-6 text-white/80">Solicita informes sobre la convocatoria, los requisitos de ingreso y las fechas de inscripción.</p>
                <a
                    :href="`mailto:${INSTITUTION.email}?subject=${encodeURIComponent(`Informes: ${program.name}`)}`"
                    class="mt-5 flex h-11 items-center justify-center gap-2 rounded-full bg-white text-sm font-bold text-[#941B2B] hover:bg-[#F9ECEE]"
                >
                    <Mail class="size-4" /> Solicitar informes
                </a>
                <a :href="INSTITUTION.phoneHref" class="mt-2 flex h-11 items-center justify-center gap-2 rounded-full border border-white/40 text-sm font-semibold hover:bg-white/10">
                    <Phone class="size-4" /> {{ INSTITUTION.phone }}
                </a>
            </div>

            <div v-if="related.length" class="space-y-3">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-[#858B93]">Programas relacionados</p>
                <ProgramCard v-for="item in related" :key="item.code" :program="item" />
            </div>
        </aside>
    </div>
</template>
