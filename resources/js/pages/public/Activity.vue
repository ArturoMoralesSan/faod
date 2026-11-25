<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Award, CalendarDays, Clock, Mail, MapPin, Mic, MonitorSmartphone, Phone, Ticket, Users } from 'lucide-vue-next';
import { computed } from 'vue';

import ActivityCard from '@/components/public/ActivityCard.vue';
import PageHero from '@/components/public/PageHero.vue';
import { formatMoney, label } from '@/lib/admin';
import { INSTITUTION } from '@/lib/institution';
import { dateRange } from '@/lib/public-site';
import type { ActivityCard as Activity } from '@/lib/public-site';
import site from '@/routes/public';

const props = defineProps<{
    activity: Activity & {
        description: string | null;
        objectives: string | null;
        schedule: string | null;
        location: string | null;
        coordinator: string | null;
        instructors: { name: string | null; role: string; internal: boolean }[];
        results: string | null;
    };
    more: Activity[];
}>();

const isPast = computed(() => ['concluida'].includes(props.activity.status) || (props.activity.ends_at ?? props.activity.starts_at ?? '') < new Date().toISOString().substring(0, 10));
const full = computed(() => props.activity.available === 0);

const FACTS = computed(() =>
    [
        { icon: CalendarDays, label: 'Fecha', value: dateRange(props.activity.starts_at, props.activity.ends_at) },
        { icon: Clock, label: 'Horario', value: props.activity.schedule },
        { icon: MapPin, label: 'Lugar', value: props.activity.location },
        { icon: MonitorSmartphone, label: 'Modalidad', value: label(props.activity.modality) },
        { icon: Clock, label: 'Duración', value: props.activity.hours ? `${props.activity.hours} horas` : null },
        { icon: Ticket, label: 'Costo', value: props.activity.cost ? formatMoney(props.activity.cost) : 'Sin costo' },
        { icon: Users, label: 'Cupo', value: props.activity.capacity ? (full.value ? 'Cupo lleno' : `${props.activity.available} de ${props.activity.capacity} lugares disponibles`) : 'Sin límite' },
        { icon: Award, label: 'Constancia', value: props.activity.issues_certificate ? 'Sí, al acreditar la asistencia mínima' : 'No' },
    ].filter((fact) => fact.value),
);
</script>

<template>
    <Head :title="activity.title" />

    <PageHero
        :eyebrow="label(activity.type)"
        :title="activity.title"
        :text="dateRange(activity.starts_at, activity.ends_at)"
        :crumbs="[{ label: 'Cursos y actividades', href: site.activities().url }, { label: activity.title }]"
    >
        <div class="mt-6 flex flex-wrap gap-2">
            <span class="rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-semibold">{{ label(activity.modality) }}</span>
            <span v-if="isPast" class="rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-semibold">Realizada</span>
            <span v-else-if="full" class="rounded-full bg-[#F2C14E] px-3 py-1 text-xs font-bold text-[#3D0912]">Cupo lleno</span>
            <span v-else class="rounded-full bg-white px-3 py-1 text-xs font-bold text-[#941B2B]">Inscripciones abiertas</span>
        </div>
    </PageHero>

    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1fr_340px]">
        <div class="min-w-0 space-y-10">
            <section v-if="activity.description">
                <h2 class="font-['Manrope',sans-serif] text-2xl font-extrabold text-[#1F2226]">Acerca de la actividad</h2>
                <p class="mt-4 whitespace-pre-line text-[17px] leading-8 text-[#525960]">{{ activity.description }}</p>
            </section>

            <section v-if="activity.objectives" class="rounded-2xl border border-[#E3E5E8] bg-white p-6">
                <h2 class="font-['Manrope',sans-serif] text-lg font-bold text-[#1F2226]">Objetivos</h2>
                <p class="mt-3 whitespace-pre-line leading-7 text-[#525960]">{{ activity.objectives }}</p>
            </section>

            <section v-if="activity.instructors.length || activity.coordinator">
                <h2 class="font-['Manrope',sans-serif] text-2xl font-extrabold text-[#1F2226]">Ponentes</h2>
                <ul class="mt-5 grid gap-3 sm:grid-cols-2">
                    <li v-for="(person, index) in activity.instructors" :key="index" class="flex items-center gap-3 rounded-2xl border border-[#E3E5E8] bg-white p-4">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-[#F9ECEE] text-[#941B2B]"><Mic class="size-5" /></span>
                        <span>
                            <span class="block font-semibold text-[#292D32]">{{ person.name }}</span>
                            <span class="block text-xs text-[#858B93]">{{ person.role }} · {{ person.internal ? 'docente de la facultad' : 'invitado' }}</span>
                        </span>
                    </li>
                    <li v-if="activity.coordinator" class="flex items-center gap-3 rounded-2xl border border-[#E3E5E8] bg-white p-4">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-[#F9ECEE] text-[#941B2B]"><Users class="size-5" /></span>
                        <span>
                            <span class="block font-semibold text-[#292D32]">{{ activity.coordinator }}</span>
                            <span class="block text-xs text-[#858B93]">Coordinación</span>
                        </span>
                    </li>
                </ul>
            </section>

            <section v-if="activity.results" class="rounded-2xl border border-[#BFE3CC] bg-[#E2F3E8] p-6">
                <h2 class="font-['Manrope',sans-serif] text-lg font-bold text-[#1F4D33]">Resultados</h2>
                <p class="mt-3 whitespace-pre-line leading-7 text-[#1F4D33]">{{ activity.results }}</p>
            </section>

            <section v-if="more.length">
                <h2 class="font-['Manrope',sans-serif] text-2xl font-extrabold text-[#1F2226]">Otras actividades próximas</h2>
                <div class="mt-6 grid gap-4">
                    <ActivityCard v-for="item in more" :key="item.id" :activity="item" />
                </div>
            </section>
        </div>

        <aside class="space-y-5 lg:sticky lg:top-24 lg:self-start">
            <dl class="divide-y divide-[#F1F2F4] rounded-2xl border border-[#E3E5E8] bg-white">
                <div v-for="fact in FACTS" :key="fact.label" class="flex gap-3 px-5 py-4">
                    <component :is="fact.icon" class="mt-0.5 size-5 shrink-0 text-[#941B2B]" />
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-[#858B93]">{{ fact.label }}</dt>
                        <dd class="text-sm font-semibold text-[#292D32]">{{ fact.value }}</dd>
                    </div>
                </div>
            </dl>

            <div v-if="!isPast" class="rounded-2xl bg-[#941B2B] p-6 text-white">
                <p class="font-['Manrope',sans-serif] text-lg font-bold">{{ full ? 'Lista de espera' : '¿Cómo me inscribo?' }}</p>
                <p class="mt-2 text-sm leading-6 text-white/80">
                    {{ full ? 'El cupo está lleno, pero puedes pedir que te avisemos si se libera un lugar.' : 'Comunícate con la facultad para registrarte. El pago se realiza en caja y recibes tu recibo con folio.' }}
                </p>
                <a
                    :href="`mailto:${INSTITUTION.email}?subject=${encodeURIComponent(`Inscripción: ${activity.title}`)}`"
                    class="mt-5 flex h-11 items-center justify-center gap-2 rounded-full bg-white text-sm font-bold text-[#941B2B] hover:bg-[#F1F2F4]"
                >
                    <Mail class="size-4" /> Escribir a la facultad
                </a>
                <a :href="INSTITUTION.phoneHref" class="mt-2 flex h-11 items-center justify-center gap-2 rounded-full border border-white/40 text-sm font-semibold hover:bg-white hover:text-[#292D32]">
                    <Phone class="size-4" /> {{ INSTITUTION.phone }}
                </a>
            </div>
        </aside>
    </div>
</template>
