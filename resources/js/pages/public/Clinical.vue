<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CheckCircle2, ClipboardCheck, DoorOpen, Hospital, Mail, MapPin, Phone, ShieldCheck, Stethoscope, UserRoundCheck } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import PageHero from '@/components/public/PageHero.vue';
import { INSTITUTION } from '@/lib/institution';
import type { ClinicCard, SpecialtyCard } from '@/lib/public-site';

const props = defineProps<{
    clinics: ClinicCard[];
    services: { group: string; items: string[] }[];
    specialties: SpecialtyCard[];
}>();

const activeGroup = ref(props.services[0]?.group ?? null);
const activeServices = computed(() => props.services.find((s) => s.group === activeGroup.value)?.items ?? []);
const totalServices = computed(() => props.services.reduce((sum, s) => sum + s.items.length, 0));

const STEPS = [
    { icon: Phone, title: 'Solicita tu cita', text: `Llama al ${INSTITUTION.phone} o acude a la facultad en horario de atención.` },
    { icon: ClipboardCheck, title: 'Valoración y expediente', text: 'Registramos tus datos, antecedentes, alergias y medicamentos; hacemos el diagnóstico y el odontograma.' },
    { icon: Stethoscope, title: 'Plan de tratamiento', text: 'Te explicamos el tratamiento por etapas y su costo antes de empezar.' },
    { icon: UserRoundCheck, title: 'Atención supervisada', text: 'Los alumnos te atienden y cada procedimiento lo revisa y valida un docente especialista.' },
];

const BRING = ['Identificación oficial', 'CURP', 'Lista de medicamentos que tomas', 'Estudios o radiografías previas, si tienes'];
</script>

<template>
    <Head title="Atención clínica" />

    <PageHero
        eyebrow="Atención clínica"
        title="Atención dental para la comunidad"
        text="En las clínicas de la facultad recibes atención de prevención, diagnóstico, tratamiento y rehabilitación bucal para todas las edades, con supervisión docente en cada paso."
        :crumbs="[{ label: 'Atención clínica' }]"
    >
        <div class="mt-8 flex flex-wrap gap-3">
            <a :href="INSTITUTION.phoneHref" class="inline-flex h-12 items-center gap-2 rounded-full bg-white px-6 text-sm font-bold text-[#941B2B] hover:bg-[#F1F2F4]">
                <Phone class="size-4" /> Pedir cita: {{ INSTITUTION.phone }}
            </a>
            <a :href="INSTITUTION.map" target="_blank" rel="noopener" class="inline-flex h-12 items-center gap-2 rounded-full border border-white/40 px-6 text-sm font-semibold hover:bg-white hover:text-[#292D32]">
                <MapPin class="size-4" /> Cómo llegar
            </a>
        </div>
    </PageHero>

    <!-- Proceso -->
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#941B2B]">Paso a paso</p>
        <h2 class="mt-2 font-['Manrope',sans-serif] text-3xl font-extrabold tracking-tight text-[#1F2226]">¿Cómo me atienden?</h2>
        <ol class="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <li v-for="(step, index) in STEPS" :key="step.title" class="relative rounded-2xl border border-[#E3E5E8] bg-white p-6">
                <span class="absolute right-5 top-4 font-['Manrope',sans-serif] text-4xl font-extrabold text-[#F1F2F4]">{{ index + 1 }}</span>
                <span class="flex size-11 items-center justify-center rounded-xl bg-[#941B2B] text-white"><component :is="step.icon" class="size-5" /></span>
                <p class="mt-4 font-['Manrope',sans-serif] text-lg font-bold text-[#1F2226]">{{ step.title }}</p>
                <p class="mt-1.5 text-sm leading-6 text-[#737982]">{{ step.text }}</p>
            </li>
        </ol>
    </section>

    <!-- Servicios -->
    <section v-if="services.length" class="bg-white">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#941B2B]">Servicios</p>
                    <h2 class="mt-2 font-['Manrope',sans-serif] text-3xl font-extrabold tracking-tight text-[#1F2226]">Tratamientos que realizamos</h2>
                </div>
                <p class="text-sm font-semibold text-[#941B2B]">{{ totalServices }} procedimientos</p>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-[260px_1fr]">
                <div class="flex gap-2 overflow-x-auto pb-1 lg:flex-col lg:overflow-visible" role="tablist" aria-label="Áreas">
                    <button
                        v-for="group in services"
                        :key="group.group"
                        type="button"
                        role="tab"
                        :aria-selected="activeGroup === group.group"
                        :class="['flex shrink-0 items-center justify-between gap-3 rounded-xl px-4 py-3 text-left text-sm font-semibold transition-colors', activeGroup === group.group ? 'bg-[#941B2B] text-white' : 'bg-[#F7F8FA] text-[#383D43] hover:bg-[#F1F2F4] hover:text-[#292D32]']"
                        @click="activeGroup = group.group"
                    >
                        {{ group.group }} <span :class="['text-xs', activeGroup === group.group ? 'text-white/75' : 'text-[#858B93]']">{{ group.items.length }}</span>
                    </button>
                </div>
                <ul class="grid content-start gap-2 sm:grid-cols-2" role="tabpanel">
                    <li v-for="service in activeServices" :key="service" class="flex items-start gap-2.5 rounded-xl border border-[#E3E5E8] px-4 py-3 text-sm text-[#383D43]">
                        <CheckCircle2 class="mt-0.5 size-4 shrink-0 text-[#941B2B]" /> {{ service }}
                    </li>
                </ul>
            </div>
            <p class="mt-6 text-xs text-[#858B93]">El costo de cada tratamiento se informa en la valoración, antes de iniciar.</p>
        </div>
    </section>

    <!-- Clínicas -->
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#941B2B]">Instalaciones</p>
        <h2 class="mt-2 font-['Manrope',sans-serif] text-3xl font-extrabold tracking-tight text-[#1F2226]">Nuestras clínicas</h2>

        <div v-if="clinics.length" class="mt-8 grid gap-4 md:grid-cols-2">
            <article v-for="clinic in clinics" :key="clinic.code" class="rounded-2xl border border-[#E3E5E8] bg-white p-6">
                <div class="flex items-start justify-between gap-3">
                    <span class="flex size-12 items-center justify-center rounded-2xl bg-[#F9ECEE] text-[#941B2B]"><Hospital class="size-6" /></span>
                    <span v-if="clinic.offices" class="inline-flex items-center gap-1.5 rounded-full bg-[#F7F8FA] px-3 py-1 text-xs font-semibold text-[#525960]">
                        <DoorOpen class="size-3.5" /> {{ clinic.offices }} consultorio{{ clinic.offices === 1 ? '' : 's' }}
                    </span>
                </div>
                <h3 class="mt-4 font-['Manrope',sans-serif] text-xl font-bold text-[#1F2226]">{{ clinic.name }}</h3>
                <p v-if="clinic.location" class="mt-1 flex items-center gap-1.5 text-sm text-[#737982]"><MapPin class="size-4" /> {{ clinic.location }}</p>
                <p v-if="clinic.description" class="mt-3 text-sm leading-6 text-[#525960]">{{ clinic.description }}</p>
                <div v-if="clinic.areas.length" class="mt-4 flex flex-wrap gap-1.5">
                    <span v-for="area in clinic.areas" :key="area.name" class="rounded-full bg-[#F9ECEE] px-3 py-1 text-xs font-semibold text-[#941B2B]">
                        {{ area.name }}<template v-if="area.specialty && area.specialty !== area.name"> · {{ area.specialty }}</template>
                    </span>
                </div>
            </article>
        </div>
        <p v-else class="mt-8 rounded-2xl border border-dashed border-[#E3E5E8] bg-white px-5 py-10 text-center text-sm text-[#737982]">
            Pronto publicaremos la información de nuestras clínicas. Para atención, llama al {{ INSTITUTION.phone }}.
        </p>

        <div v-if="specialties.length" class="mt-12">
            <p class="text-sm font-semibold text-[#383D43]">Especialidades con atención en la facultad</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <span v-for="specialty in specialties" :key="specialty.code" class="rounded-full border border-[#E3E5E8] bg-white px-3.5 py-1.5 text-sm text-[#383D43]">{{ specialty.name }}</span>
            </div>
        </div>
    </section>

    <!-- Qué llevar -->
    <section class="bg-[#5E0F1B] text-white">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-2">
            <div>
                <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-[#F6D6DB]"><ShieldCheck class="size-4" /> Tu primera cita</p>
                <h2 class="mt-2 font-['Manrope',sans-serif] text-3xl font-extrabold">¿Qué debo llevar?</h2>
                <ul class="mt-6 space-y-3">
                    <li v-for="item in BRING" :key="item" class="flex items-center gap-3 text-white/85"><CheckCircle2 class="size-5 shrink-0 text-[#F2C14E]" /> {{ item }}</li>
                </ul>
            </div>
            <div class="rounded-3xl bg-white p-7 text-[#292D32]">
                <p class="font-['Manrope',sans-serif] text-xl font-bold text-[#1F2226]">Agenda tu valoración</p>
                <p class="mt-2 text-sm leading-6 text-[#737982]">Te damos fecha y hora en la clínica que corresponda a tu tratamiento.</p>
                <a :href="INSTITUTION.phoneHref" class="mt-5 flex h-12 items-center justify-center gap-2 rounded-full bg-[#941B2B] text-sm font-bold text-white hover:bg-white hover:text-[#292D32] hover:ring-1 hover:ring-inset hover:ring-[#941B2B]">
                    <Phone class="size-4" /> {{ INSTITUTION.phone }}
                </a>
                <a :href="`mailto:${INSTITUTION.email}`" class="mt-2 flex h-12 items-center justify-center gap-2 rounded-full border border-[#E3E5E8] text-sm font-semibold text-[#383D43] hover:border-[#B8BEC6] hover:text-[#292D32]">
                    <Mail class="size-4" /> {{ INSTITUTION.email }}
                </a>
                <p class="mt-4 flex items-start gap-2 text-xs text-[#858B93]"><MapPin class="mt-0.5 size-4 shrink-0" /> {{ INSTITUTION.address }}</p>
            </div>
        </div>
    </section>
</template>
