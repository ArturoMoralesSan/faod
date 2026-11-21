<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { LogIn, ShieldAlert } from 'lucide-vue-next';

import ActivityLogTabs from '@/components/admin/ActivityLogTabs.vue';
import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterChip from '@/components/admin/FilterChip.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { filterValue, useIndexFilters } from '@/composables/useIndexFilters';
import { formatDateTime, recordOptions } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option, Paginated } from '@/types/admin';

interface LoginRow {
    id: number;
    event: 'login' | 'logout' | 'failed';
    email: string | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
    user: { id: number; name: string; email: string } | null;
}

const props = defineProps<{
    logs: Paginated<LoginRow>;
    filters: Record<string, string | undefined>;
    events: string[];
    users: Option[];
    failedToday: number;
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.activityLog.logins().url,
    {
        search: filterValue(props.filters.search),
        event: filterValue(props.filters.event),
        user_id: filterValue(props.filters.user_id),
        date_from: filterValue(props.filters.date_from),
        date_to: filterValue(props.filters.date_to),
    },
);

const EVENTS: Record<string, { label: string; classes: string }> = {
    login: { label: 'Inició sesión', classes: 'bg-[#E8F5EE] text-[#1F7A4D]' },
    logout: { label: 'Cerró sesión', classes: 'bg-[#F1F2F4] text-[#525960]' },
    failed: { label: 'Intento fallido', classes: 'bg-[#F9ECEE] text-[#941B2B]' },
};

const eventOptions = props.events.map((value) => ({ value, label: EVENTS[value]?.label ?? value }));
const userName = (id: string) => props.users.find((u) => String(u.id) === id)?.name;

/** Navegador y sistema operativo legibles a partir del user agent. */
const device = (agent: string | null): string => {
    if (!agent) {
        return '—';
    }

    const browser = /Edg\//.test(agent) ? 'Edge' : /OPR\//.test(agent) ? 'Opera' : /Chrome\//.test(agent) ? 'Chrome' : /Firefox\//.test(agent) ? 'Firefox' : /Safari\//.test(agent) ? 'Safari' : 'Otro navegador';
    const os = /Windows/.test(agent) ? 'Windows' : /Android/.test(agent) ? 'Android' : /iPhone|iPad/.test(agent) ? 'iOS' : /Mac OS/.test(agent) ? 'macOS' : /Linux/.test(agent) ? 'Linux' : '';

    return [browser, os].filter(Boolean).join(' · ');
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Historial', href: admin.activityLog.index() },
            { title: 'Accesos', href: admin.activityLog.logins() },
        ],
    },
});
</script>

<template>
    <Head title="Bitácora de accesos" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader title="Bitácora de accesos" description="Quién entró al sistema, cuándo y desde dónde; incluye intentos fallidos." section="Configuración" :icon="LogIn" />

        <div class="flex flex-wrap items-center justify-between gap-3">
            <ActivityLogTabs active="logins" />
            <p v-if="failedToday" class="inline-flex items-center gap-1.5 rounded-full bg-[#F9ECEE] px-3 py-1.5 text-xs font-semibold text-[#941B2B]">
                <ShieldAlert class="h-4 w-4" /> {{ failedToday }} intento(s) fallido(s) hoy
            </p>
        </div>

        <DataTable
            v-model:search="filters.search"
            :pagination="logs"
            :is-empty="logs.data.length === 0"
            :empty-colspan="5"
            search-placeholder="Buscar por correo o IP..."
            :has-filters="hasFilters"
            @search="submit"
            @clear-filters="clear"
        >
            <template #filters>
                <FilterSelect v-model="filters.event" placeholder="Todos los eventos" :options="eventOptions" @change="submit" />
                <FilterSelect v-model="filters.user_id" placeholder="Todos los usuarios" :options="recordOptions(users)" @change="submit" />
                <input v-model="filters.date_from" type="date" class="form-input h-[42px] w-auto" title="Desde" @change="submit" />
                <input v-model="filters.date_to" type="date" class="form-input h-[42px] w-auto" title="Hasta" @change="submit" />
            </template>

            <template #active-filters>
                <FilterChip v-if="filters.search">Búsqueda: {{ filters.search }}</FilterChip>
                <FilterChip v-if="filters.event">Evento: {{ EVENTS[filters.event]?.label }}</FilterChip>
                <FilterChip v-if="filters.user_id">Usuario: {{ userName(filters.user_id) }}</FilterChip>
                <FilterChip v-if="filters.date_from">Desde: {{ filters.date_from }}</FilterChip>
                <FilterChip v-if="filters.date_to">Hasta: {{ filters.date_to }}</FilterChip>
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Fecha</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Usuario</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Evento</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">IP</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Dispositivo</th>
                </tr>
            </template>

            <template #table-body>
                <tr v-for="log in logs.data" :key="log.id" class="transition-colors hover:bg-[#FAF3F4]">
                    <td class="whitespace-nowrap px-5 py-4 text-[#525960] sm:px-6">{{ formatDateTime(log.created_at) }}</td>
                    <td class="px-5 py-4 sm:px-6">
                        <p class="text-[#292D32]">{{ log.user?.name ?? 'Usuario desconocido' }}</p>
                        <p class="text-xs text-[#858B93]">{{ log.user?.email ?? log.email ?? '—' }}</p>
                    </td>
                    <td class="px-5 py-4 sm:px-6">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="EVENTS[log.event]?.classes">{{ EVENTS[log.event]?.label ?? log.event }}</span>
                    </td>
                    <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-[#525960] sm:px-6">{{ log.ip_address ?? '—' }}</td>
                    <td class="px-5 py-4 text-xs text-[#525960] sm:px-6" :title="log.user_agent ?? ''">{{ device(log.user_agent) }}</td>
                </tr>
            </template>

            <template #empty>
                <EmptyState :icon="LogIn" title="Sin accesos" :description="hasFilters ? 'Prueba con otros filtros.' : 'Aún no hay accesos registrados.'" />
            </template>
        </DataTable>
    </div>
</template>
