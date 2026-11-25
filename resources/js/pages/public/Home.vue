<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpenCheck,
    CalendarDays,
    ClipboardCheck,
    ExternalLink,
    GraduationCap,
    Hospital,
    Mail,
    MapPin,
    Phone,
    Stethoscope,
    UserRoundCheck,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

import ActivityCard from '@/components/public/ActivityCard.vue';
import ProgramCard from '@/components/public/ProgramCard.vue';
import SectionHeading from '@/components/public/SectionHeading.vue';
import { IMAGES, INSTITUTION } from '@/lib/institution';
import { LEVEL_TITLES } from '@/lib/public-site';
import type { ActivityCard as Activity, ClinicCard, LevelGroup, SpecialtyCard } from '@/lib/public-site';
import site from '@/routes/public';

const props = defineProps<{
    stats: { programs: number; specialties: number; clinics: number; activities: number };
    levels: LevelGroup[];
    upcoming: Activity[];
    recent: Activity[];
    specialties: SpecialtyCard[];
    clinics: ClinicCard[];
    servicesCount: number;
}>();

const activeLevel = ref(props.levels[0]?.level ?? null);
const levelPrograms = computed(() => props.levels.find((l) => l.level === activeLevel.value)?.programs.slice(0, 6) ?? []);

const STATS = computed(() => [
    { value: props.stats.programs, label: 'programas académicos', icon: GraduationCap },
    { value: props.stats.specialties, label: 'áreas de especialidad', icon: BookOpenCheck },
    { value: props.stats.clinics, label: 'clínicas de atención', icon: Hospital },
    { value: props.stats.activities, label: `cursos y actividades en ${new Date().getFullYear()}`, icon: CalendarDays },
]);

const STEPS = [
    { icon: Phone, title: 'Comunícate con la facultad', text: `Llama al ${INSTITUTION.phone} o acude a la clínica para solicitar una cita.` },
    { icon: ClipboardCheck, title: 'Valoración inicial', text: 'Se abre tu expediente clínico con tus antecedentes, diagnóstico y odontograma.' },
    { icon: UserRoundCheck, title: 'Tratamiento supervisado', text: 'Te atienden alumnos de la facultad, siempre con la supervisión y validación de un docente.' },
];
</script>

<template>
    <Head :title="`${INSTITUTION.name} · ${INSTITUTION.universityShort}`" />

    <!-- Portada -->
    <section class="relative isolate overflow-hidden bg-[#5E0F1B] text-white">
        <img :src="IMAGES.campus" alt="Campus de la Facultad de Odontología, UJED" class="absolute inset-0 -z-10 h-full w-full object-cover" style="object-position: 20% center" />
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-[#2A060C]/95 via-[#5E0F1B]/85 to-[#941B2B]/40" />
        <div class="pointer-events-none absolute -right-40 -top-40 -z-10 size-[520px] rounded-full border border-white/10" />
        <div class="pointer-events-none absolute -right-20 -top-20 -z-10 size-[360px] rounded-full border border-white/10" />

        <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 pb-32 pt-20 sm:px-6 lg:grid-cols-[1.25fr_1fr] lg:pt-28">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3.5 py-1.5 text-[11px] font-semibold uppercase tracking-[0.14em] backdrop-blur-sm">
                    <span class="size-1.5 rounded-full bg-[#F2C14E]" /> {{ INSTITUTION.university }}
                </p>
                <h1 class="mt-6 font-['Manrope',sans-serif] text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-5xl lg:text-6xl">
                    {{ INSTITUTION.name }}
                </h1>
                <p class="mt-5 max-w-xl text-base leading-7 text-white/85 sm:text-lg">
                    Formamos odontólogos y especialistas con sentido ético y humano, y ofrecemos atención dental a la comunidad en nuestras clínicas.
                </p>
                <div class="mt-9 flex flex-wrap gap-3">
                    <Link
                        :href="site.programs().url"
                        class="inline-flex h-12 items-center gap-2 rounded-full bg-white px-6 text-sm font-bold text-[#941B2B] transition-colors hover:bg-[#F1F2F4] focus-visible:outline focus-visible:outline-[3px] focus-visible:outline-offset-2 focus-visible:outline-white"
                    >
                        Oferta académica <ArrowRight class="size-4" />
                    </Link>
                    <Link :href="site.clinical().url" class="inline-flex h-12 items-center gap-2 rounded-full border border-white/40 px-6 text-sm font-semibold text-white transition-colors hover:bg-white hover:text-[#292D32]">
                        <Stethoscope class="size-4" /> Atención clínica
                    </Link>
                </div>
            </div>

            <!-- Próxima actividad destacada -->
            <Link
                v-if="upcoming[0]"
                :href="site.activities.show(upcoming[0].id).url"
                class="group hidden rounded-3xl border border-white/15 bg-white/10 p-6 backdrop-blur-md transition-colors hover:bg-white/15 lg:block"
            >
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-[#F6D6DB]">Próximo evento</p>
                <p class="mt-3 font-['Manrope',sans-serif] text-2xl font-extrabold leading-snug">{{ upcoming[0].title }}</p>
                <p v-if="upcoming[0].summary" class="mt-2 line-clamp-3 text-sm leading-6 text-white/75">{{ upcoming[0].summary }}</p>
                <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-bold">Ver detalles <ArrowRight class="size-4 transition-transform group-hover:translate-x-0.5" /></span>
            </Link>
        </div>
    </section>

    <!-- Cifras -->
    <section class="relative z-10 mx-auto -mt-16 max-w-6xl px-4 sm:px-6">
        <dl class="grid grid-cols-2 gap-px overflow-hidden rounded-3xl border border-[#E3E5E8] bg-[#E3E5E8] shadow-[0_20px_50px_-25px_rgba(42,6,12,0.35)] lg:grid-cols-4">
            <div v-for="stat in STATS" :key="stat.label" class="flex items-center gap-4 bg-white px-5 py-6 sm:px-7">
                <span class="hidden size-12 shrink-0 items-center justify-center rounded-2xl bg-[#F9ECEE] text-[#941B2B] sm:flex"><component :is="stat.icon" class="size-6" /></span>
                <div>
                    <dt class="sr-only">{{ stat.label }}</dt>
                    <dd class="font-['Manrope',sans-serif] text-3xl font-extrabold text-[#1F2226]">{{ stat.value }}</dd>
                    <p class="text-xs font-medium leading-snug text-[#737982]">{{ stat.label }}</p>
                </div>
            </div>
        </dl>
    </section>

    <!-- Oferta académica -->
    <section class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
        <SectionHeading
            eyebrow="Oferta académica"
            title="Licenciatura y posgrados"
            text="Programas con planes académicos propios, formación clínica supervisada y docentes especialistas."
            :link-href="levels.length ? site.programs().url : undefined"
            link-label="Ver toda la oferta"
        />

        <template v-if="levels.length">
            <div class="mt-8 flex flex-wrap gap-2" role="tablist" aria-label="Niveles">
                <button
                    v-for="group in levels"
                    :key="group.level"
                    type="button"
                    role="tab"
                    :aria-selected="activeLevel === group.level"
                    :class="['rounded-full border px-4 py-2 text-sm font-semibold transition-colors', activeLevel === group.level ? 'border-[#941B2B] bg-[#941B2B] text-white' : 'border-[#E3E5E8] bg-white text-[#525960] hover:border-[#B8BEC6] hover:text-[#292D32]']"
                    @click="activeLevel = group.level"
                >
                    {{ LEVEL_TITLES[group.level]?.title ?? group.level }}
                    <span :class="['ml-1 text-xs', activeLevel === group.level ? 'text-white/75' : 'text-[#858B93]']">{{ group.programs.length }}</span>
                </button>
            </div>
            <p v-if="activeLevel" class="mt-4 text-sm text-[#737982]">{{ LEVEL_TITLES[activeLevel]?.text }}</p>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <ProgramCard v-for="program in levelPrograms" :key="program.code" :program="program" />
            </div>
        </template>
        <div v-else class="mt-8 rounded-3xl border border-dashed border-[#E8C7CC] bg-white px-6 py-12 text-center">
            <GraduationCap class="mx-auto size-10 text-[#941B2B]" />
            <p class="mt-3 font-['Manrope',sans-serif] text-lg font-bold text-[#1F2226]">La oferta académica se publicará próximamente</p>
            <p class="mt-1 text-sm text-[#737982]">Para informes, escribe a <a :href="`mailto:${INSTITUTION.email}`" class="font-semibold text-[#941B2B] hover:underline">{{ INSTITUTION.email }}</a>.</p>
        </div>
    </section>

    <!-- Cursos y actividades -->
    <section class="bg-white">
        <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
            <SectionHeading
                eyebrow="Educación continua"
                title="Cursos, congresos y actividades"
                text="Seminarios, talleres, jornadas y congresos para alumnos, egresados y profesionales de la salud bucal."
                :link-href="site.activities().url"
                link-label="Ver calendario completo"
            />

            <div v-if="upcoming.length || recent.length" class="mt-10 grid gap-10 lg:grid-cols-[1.4fr_1fr]">
                <div>
                    <p class="mb-4 text-xs font-bold uppercase tracking-[0.14em] text-[#858B93]">Próximos</p>
                    <div v-if="upcoming.length" class="grid gap-4">
                        <ActivityCard v-for="activity in upcoming" :key="activity.id" :activity="activity" />
                    </div>
                    <p v-else class="rounded-2xl border border-dashed border-[#E3E5E8] px-5 py-8 text-center text-sm text-[#737982]">No hay actividades programadas por ahora.</p>
                </div>
                <div>
                    <p class="mb-4 text-xs font-bold uppercase tracking-[0.14em] text-[#858B93]">Recientes</p>
                    <div v-if="recent.length" class="grid gap-4">
                        <ActivityCard v-for="activity in recent" :key="activity.id" :activity="activity" past />
                    </div>
                    <p v-else class="rounded-2xl border border-dashed border-[#E3E5E8] px-5 py-8 text-center text-sm text-[#737982]">Aún no hay actividades realizadas.</p>
                </div>
            </div>
            <div v-else class="mt-8 rounded-3xl border border-dashed border-[#E8C7CC] px-6 py-12 text-center">
                <CalendarDays class="mx-auto size-10 text-[#941B2B]" />
                <p class="mt-3 font-['Manrope',sans-serif] text-lg font-bold text-[#1F2226]">Pronto publicaremos el calendario</p>
                <p class="mt-1 text-sm text-[#737982]">Aquí aparecerán los cursos, seminarios y congresos de la facultad.</p>
            </div>
        </div>
    </section>

    <!-- Especialidades -->
    <section v-if="specialties.length" class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
        <SectionHeading eyebrow="Especialidades" title="Áreas de la odontología que cultivamos" text="Cada área tiene formación de posgrado, docentes especialistas o atención clínica en la facultad." />
        <div class="mt-10 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <component
                :is="specialty.program_code ? Link : 'div'"
                v-for="specialty in specialties"
                :key="specialty.code"
                :href="specialty.program_code ? site.programs.show(specialty.program_code).url : undefined"
                :class="['flex items-start gap-4 rounded-2xl border border-[#E3E5E8] bg-white p-5', specialty.program_code ? 'group transition-colors hover:border-[#B8BEC6]' : '']"
            >
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#941B2B] font-['Manrope',sans-serif] text-sm font-extrabold text-white">
                    {{ specialty.name.charAt(0) }}
                </span>
                <span class="min-w-0">
                    <span class="block font-['Manrope',sans-serif] font-bold text-[#1F2226] group-hover:text-[#292D32]">{{ specialty.name }}</span>
                    <span v-if="specialty.description" class="mt-1 block line-clamp-2 text-sm text-[#737982]">{{ specialty.description }}</span>
                    <span v-if="specialty.programs" class="mt-1.5 inline-block text-xs font-semibold text-[#941B2B]">{{ specialty.programs }} programa{{ specialty.programs === 1 ? '' : 's' }} de posgrado</span>
                </span>
            </component>
        </div>
    </section>

    <!-- Atención clínica -->
    <section class="relative isolate overflow-hidden bg-[#5E0F1B] text-white">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_85%_15%,rgba(255,255,255,0.12),transparent_45%)]" />
        <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
            <SectionHeading
                eyebrow="Atención clínica"
                title="Atención dental para la comunidad"
                :text="`Nuestras clínicas ofrecen ${servicesCount ? `${servicesCount} procedimientos` : 'servicios'} de prevención, diagnóstico, tratamiento y rehabilitación bucal para todas las edades.`"
                :link-href="site.clinical().url"
                link-label="Clínicas y servicios"
                light
            />

            <ol class="mt-10 grid gap-4 md:grid-cols-3">
                <li v-for="(step, index) in STEPS" :key="step.title" class="rounded-2xl border border-white/15 bg-white/[0.07] p-6">
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-white text-[#941B2B]"><component :is="step.icon" class="size-5" /></span>
                        <span class="text-xs font-bold uppercase tracking-[0.16em] text-white/60">Paso {{ index + 1 }}</span>
                    </div>
                    <p class="mt-4 font-['Manrope',sans-serif] text-lg font-bold">{{ step.title }}</p>
                    <p class="mt-1.5 text-sm leading-6 text-white/75">{{ step.text }}</p>
                </li>
            </ol>

            <div v-if="clinics.length" class="mt-6 grid gap-4 md:grid-cols-3">
                <div v-for="clinic in clinics" :key="clinic.code" class="rounded-2xl bg-white p-6 text-[#292D32]">
                    <Hospital class="size-6 text-[#941B2B]" />
                    <p class="mt-3 font-['Manrope',sans-serif] text-lg font-bold text-[#1F2226]">{{ clinic.name }}</p>
                    <p v-if="clinic.location" class="mt-1 flex items-center gap-1.5 text-xs text-[#737982]"><MapPin class="size-3.5" /> {{ clinic.location }}</p>
                    <div v-if="clinic.areas.length" class="mt-3 flex flex-wrap gap-1.5">
                        <span v-for="area in clinic.areas" :key="area.name" class="rounded-full bg-[#F9ECEE] px-2.5 py-0.5 text-[11px] font-semibold text-[#941B2B]">{{ area.name }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- La facultad y contacto -->
    <section class="bg-white">
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2">
            <figure class="overflow-hidden rounded-3xl border border-[#E3E5E8]">
                <img :src="IMAGES.campus" alt="Edificio de la Facultad de Odontología" class="aspect-[4/3] w-full object-cover" style="object-position: 12% center" loading="lazy" />
            </figure>
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#941B2B]">La facultad</p>
                <h2 class="mt-2 font-['Manrope',sans-serif] text-3xl font-extrabold tracking-tight text-[#1F2226] sm:text-4xl">Formación odontológica con sentido humano</h2>
                <p class="mt-5 leading-8 text-[#525960]">{{ INSTITUTION.purpose }}</p>

                <ul class="mt-8 space-y-3 text-sm">
                    <li>
                        <a :href="INSTITUTION.map" target="_blank" rel="noopener" class="flex items-start gap-3 text-[#383D43] hover:text-[#292D32]">
                            <MapPin class="mt-0.5 size-5 shrink-0 text-[#941B2B]" /> {{ INSTITUTION.address }}
                        </a>
                    </li>
                    <li><a :href="INSTITUTION.phoneHref" class="flex items-center gap-3 text-[#383D43] hover:text-[#292D32]"><Phone class="size-5 text-[#941B2B]" /> {{ INSTITUTION.phone }}</a></li>
                    <li><a :href="`mailto:${INSTITUTION.email}`" class="flex items-center gap-3 text-[#383D43] hover:text-[#292D32]"><Mail class="size-5 text-[#941B2B]" /> {{ INSTITUTION.email }}</a></li>
                </ul>
                <a :href="INSTITUTION.website" target="_blank" rel="noopener" class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-[#941B2B] hover:underline">
                    Página de la facultad en ujed.mx <ExternalLink class="size-3.5" />
                </a>
            </div>
        </div>
    </section>
</template>
