<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Banknote,
    CalendarCheck,
    CalendarClock,
    CalendarRange,
    Receipt,
    ClipboardList,
    Contact,
    GraduationCap,
    LayoutGrid,
    Package,
    LogOut,
    MousePointerClick,
    ShieldCheck,
    TriangleAlert,
    UserRoundCheck,
    Users,
    Wrench,
} from 'lucide-vue-next';
import type { Component } from 'vue';

import SeverityBadge from '@/components/admin/SeverityBadge.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { formatDate, formatDateTime, formatMoney, formatTime, fullName } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option, PersonName } from '@/types/admin';

interface Stat {
    label: string;
    value: number;
    url: string;
    icon: string;
    tone?: string;
    format?: 'money';
}

const props = defineProps<{
    period: { id: number; name: string } | null;
    stats: Stat[];
    todayAppointments: {
        id: number;
        start_time: string;
        status: string;
        reason: string | null;
        patient: { people: PersonName };
        clinic: Option;
        space: { code: string } | null;
    }[] | null;
    todaySessions: {
        id: number;
        start_time: string;
        end_time: string;
        status: string;
        topic: string | null;
        clinic: Option;
        space: { code: string; name: string } | null;
        group: { code: string } | null;
        supervisor: { people: PersonName | null } | null;
    }[] | null;
    openIncidents: { id: number; title: string; severity: string; status: string; occurred_at: string; clinic: Option }[] | null;
    maintenanceDue: { id: number; code: string; name: string; next_maintenance_at: string; clinic: Option }[] | null;
}>();

const icons: Record<string, Component> = {
    Banknote,
    Receipt,
    CalendarCheck,
    CalendarClock,
    CalendarRange,
    ClipboardList,
    Contact,
    GraduationCap,
    Package,
    TriangleAlert,
    UserRoundCheck,
    Users,
};

const today = new Date().toISOString().substring(0, 10);
const overdue = (date: string) => date.substring(0, 10) < today;

const hasLists = [props.todayAppointments, props.todaySessions, props.openIncidents, props.maintenanceDue].some((list) => list !== null);
</script>

<template>
    <Head title="Inicio | Facultad de Odontología - UJED" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto bg-[#F7F8FA] p-4 sm:p-6 lg:px-8"
    >
        <!-- Bienvenida principal -->
        <section
            class="relative overflow-hidden rounded-2xl border border-[#E3E5E8] bg-white p-6 sm:p-10"
        >
            <!-- Elementos decorativos -->
            <div
                class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-[#F9ECEE]"
            />

            <div
                class="pointer-events-none absolute -bottom-24 right-36 h-48 w-48 rounded-full bg-[#F7F8FA]"
            />

            <div class="relative z-10 max-w-3xl">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]"
                    >
                        <GraduationCap class="h-6 w-6" />
                    </div>

                    <div>
                        <p
                            class="text-xs font-bold uppercase tracking-[0.16em] text-[#941B2B]"
                        >
                            Universidad Juárez del Estado de Durango
                        </p>

                        <p class="mt-0.5 text-xs font-medium text-[#737982]">
                            Facultad de Odontología
                        </p>
                    </div>
                </div>

                <h1
                    class="mt-7 text-3xl font-bold tracking-tight text-[#292D32] sm:text-4xl"
                >
                    Bienvenido al sistema de gestión interna
                </h1>

                <p class="mt-3 max-w-2xl text-sm leading-7 text-[#737982] sm:text-base">
                    Administra y consulta las actividades académicas,
                    administrativas y clínicas de la Facultad desde una sola
                    plataforma.
                </p>

                <div
                    class="mt-6 h-1 w-16 rounded-full bg-[#941B2B]"
                />
            </div>
        </section>

        <!-- Indicadores -->
        <section v-if="stats.length" class="space-y-3">
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="text-lg font-bold text-[#292D32]">Resumen</h2>
                <p v-if="period" class="text-sm text-[#737982]">Periodo vigente: {{ period.name }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
                <Link
                    v-for="stat in stats"
                    :key="stat.label"
                    :href="stat.url"
                    class="rounded-2xl border border-[#E3E5E8] bg-white p-4 transition-shadow hover:shadow-md"
                >
                    <div
                        :class="[
                            'flex h-9 w-9 items-center justify-center rounded-xl',
                            stat.tone === 'alert' && stat.value > 0 ? 'bg-[#941B2B] text-white' : 'bg-[#F9ECEE] text-[#941B2B]',
                        ]"
                    >
                        <component :is="icons[stat.icon] ?? LayoutGrid" class="h-5 w-5" />
                    </div>
                    <p class="mt-3 text-2xl font-bold text-[#292D32]">{{ stat.format === 'money' ? formatMoney(stat.value) : stat.value.toLocaleString('es-MX') }}</p>
                    <p class="text-xs font-medium text-[#737982]">{{ stat.label }}</p>
                </Link>
            </div>
        </section>

        <!-- Listas del día -->
        <section v-if="hasLists" class="grid grid-cols-1 gap-4 lg:grid-cols-2 2xl:grid-cols-4">
            <article v-if="todayAppointments !== null" class="rounded-2xl border border-[#E3E5E8] bg-white p-5">
                <div class="mb-3 flex items-center gap-2">
                    <CalendarRange class="h-5 w-5 text-[#941B2B]" />
                    <h3 class="text-base font-bold text-[#292D32]">Citas de hoy</h3>
                </div>
                <ul v-if="todayAppointments.length" class="divide-y divide-[#E3E5E8]">
                    <li v-for="a in todayAppointments" :key="a.id">
                        <Link :href="admin.appointments.index().url" class="flex items-center justify-between gap-2 py-2.5 hover:text-[#292D32]">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold">{{ formatTime(a.start_time) }} · {{ fullName(a.patient.people) }}</p>
                                <p class="truncate text-xs text-[#858B93]">{{ [a.clinic.name, a.space?.code, a.reason].filter(Boolean).join(' · ') }}</p>
                            </div>
                            <StatusBadge :value="a.status" />
                        </Link>
                    </li>
                </ul>
                <p v-else class="text-sm italic text-[#858B93]">No hay citas para hoy.</p>
            </article>

            <article v-if="todaySessions !== null" class="rounded-2xl border border-[#E3E5E8] bg-white p-5">
                <div class="mb-3 flex items-center gap-2">
                    <CalendarCheck class="h-5 w-5 text-[#941B2B]" />
                    <h3 class="text-base font-bold text-[#292D32]">Sesiones clínicas de hoy</h3>
                </div>
                <ul v-if="todaySessions.length" class="divide-y divide-[#E3E5E8]">
                    <li v-for="session in todaySessions" :key="session.id">
                        <Link :href="admin.clinicalSessions.show(session.id).url" class="flex items-center justify-between gap-2 py-2.5 hover:text-[#292D32]">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold">{{ formatTime(session.start_time) }}–{{ formatTime(session.end_time) }} · {{ session.clinic.name }}</p>
                                <p class="truncate text-xs text-[#858B93]">
                                    {{ [session.space?.name, session.group ? `Grupo ${session.group.code}` : null, session.supervisor ? fullName(session.supervisor.people) : null, session.topic].filter(Boolean).join(' · ') }}
                                </p>
                            </div>
                            <StatusBadge :value="session.status" />
                        </Link>
                    </li>
                </ul>
                <p v-else class="text-sm italic text-[#858B93]">No hay sesiones programadas para hoy.</p>
            </article>

            <article v-if="openIncidents !== null" class="rounded-2xl border border-[#E3E5E8] bg-white p-5">
                <div class="mb-3 flex items-center gap-2">
                    <TriangleAlert class="h-5 w-5 text-[#941B2B]" />
                    <h3 class="text-base font-bold text-[#292D32]">Incidencias abiertas</h3>
                </div>
                <ul v-if="openIncidents.length" class="divide-y divide-[#E3E5E8]">
                    <li v-for="incident in openIncidents" :key="incident.id">
                        <Link :href="admin.clinicIncidents.show(incident.id).url" class="flex items-center justify-between gap-2 py-2.5 hover:text-[#292D32]">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">{{ incident.title }}</p>
                                <p class="text-xs text-[#858B93]">{{ incident.clinic.name }} · {{ formatDateTime(incident.occurred_at) }}</p>
                            </div>
                            <SeverityBadge :value="incident.severity" />
                        </Link>
                    </li>
                </ul>
                <p v-else class="text-sm italic text-[#858B93]">Sin incidencias pendientes.</p>
            </article>

            <article v-if="maintenanceDue !== null" class="rounded-2xl border border-[#E3E5E8] bg-white p-5">
                <div class="mb-3 flex items-center gap-2">
                    <Wrench class="h-5 w-5 text-[#941B2B]" />
                    <h3 class="text-base font-bold text-[#292D32]">Mantenimiento próximo o vencido</h3>
                </div>
                <ul v-if="maintenanceDue.length" class="divide-y divide-[#E3E5E8]">
                    <li v-for="item in maintenanceDue" :key="item.id">
                        <Link :href="admin.clinics.show(item.clinic.id).url" class="flex items-center justify-between gap-2 py-2.5 hover:text-[#292D32]">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">{{ item.name }}</p>
                                <p class="text-xs text-[#858B93]">{{ item.code }} · {{ item.clinic.name }}</p>
                            </div>
                            <span :class="['whitespace-nowrap text-xs font-semibold', overdue(item.next_maintenance_at) ? 'text-[#941B2B]' : 'text-[#8A5A00]']">
                                {{ overdue(item.next_maintenance_at) ? 'Vencido' : 'Vence' }} {{ formatDate(item.next_maintenance_at) }}
                            </span>
                        </Link>
                    </li>
                </ul>
                <p v-else class="text-sm italic text-[#858B93]">Ningún equipo requiere mantenimiento pronto.</p>
            </article>
        </section>

        <!-- Guía de uso -->
        <section class="space-y-4">
            <div>
                <h2 class="text-lg font-bold text-[#292D32]">
                    Guía de uso
                </h2>

                <p class="mt-1 text-sm leading-6 text-[#737982]">
                    Consulta estas indicaciones para utilizar correctamente
                    el sistema.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <!-- Navegación -->
                <article
                    class="rounded-2xl border border-[#E3E5E8] bg-white p-5 transition-shadow duration-200 hover:shadow-md sm:p-6"
                >
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]"
                    >
                        <MousePointerClick class="h-5 w-5" />
                    </div>

                    <h3 class="mt-4 text-base font-bold text-[#292D32]">
                        Navegación del sistema
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-[#737982]">
                        Utiliza el menú lateral para acceder a los diferentes
                        módulos del sistema. Selecciona la sección que
                        necesitas y consulta las opciones disponibles de
                        acuerdo con los permisos asignados a tu cuenta.
                    </p>
                </article>

                <!-- Cerrar sesión -->
                <article
                    class="rounded-2xl border border-[#E3E5E8] bg-white p-5 transition-shadow duration-200 hover:shadow-md sm:p-6"
                >
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]"
                    >
                        <LogOut class="h-5 w-5" />
                    </div>

                    <h3 class="mt-4 text-base font-bold text-[#292D32]">
                        Cómo cerrar sesión
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-[#737982]">
                        Cuando termines tus actividades, sigue estos pasos
                        para salir de tu cuenta:
                    </p>

                    <ol class="mt-4 space-y-3">
                        <li class="flex items-start gap-3">
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#F9ECEE] text-xs font-bold text-[#941B2B]"
                            >
                                1
                            </span>

                            <p class="text-sm leading-6 text-[#737982]">
                                Dirígete a la parte inferior del menú lateral.
                            </p>
                        </li>

                        <li class="flex items-start gap-3">
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#F9ECEE] text-xs font-bold text-[#941B2B]"
                            >
                                2
                            </span>

                            <p class="text-sm leading-6 text-[#737982]">
                                Localiza tu nombre o abre las opciones de tu
                                cuenta.
                            </p>
                        </li>

                        <li class="flex items-start gap-3">
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#F9ECEE] text-xs font-bold text-[#941B2B]"
                            >
                                3
                            </span>

                            <p class="text-sm leading-6 text-[#737982]">
                                Selecciona
                                <strong class="font-semibold text-[#292D32]">
                                    Cerrar sesión
                                </strong>
                                para finalizar tu acceso al sistema.
                            </p>
                        </li>
                    </ol>
                </article>
            </div>
        </section>

        <!-- Seguridad -->
        <section
            class="flex items-start gap-4 rounded-2xl border border-[#E3E5E8] bg-white p-5 sm:p-6"
        >
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]"
            >
                <ShieldCheck class="h-5 w-5" />
            </div>

            <div>
                <h2 class="text-sm font-bold text-[#292D32]">
                    Seguridad de tu cuenta
                </h2>

                <p class="mt-1 text-sm leading-6 text-[#737982]">
                    Protege tus credenciales y evita compartirlas con otras
                    personas. Si utilizas un equipo compartido, cierra tu
                    sesión antes de retirarte para proteger la información
                    académica, administrativa y clínica de la Facultad de
                    Odontología.
                </p>
            </div>
        </section>
    </div>
</template>
