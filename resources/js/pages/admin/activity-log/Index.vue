<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ChevronDown, FileClock, History } from 'lucide-vue-next';
import { ref } from 'vue';

import ActivityLogTabs from '@/components/admin/ActivityLogTabs.vue';
import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterChip from '@/components/admin/FilterChip.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { filterValue, useIndexFilters } from '@/composables/useIndexFilters';
import { formatDateTime, label, recordOptions } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option, Paginated, SelectOption } from '@/types/admin';

interface LogEntry {
    id: number;
    event: 'created' | 'updated' | 'deleted' | 'restored';
    subject_type: string;
    subject_name: string;
    subject_id: number;
    subject_label: string | null;
    changes: Record<string, [unknown, unknown]> | null;
    ip_address: string | null;
    created_at: string;
    user: { id: number; name: string } | null;
}

const props = defineProps<{
    logs: Paginated<LogEntry>;
    filters: Record<string, string | undefined>;
    subjectTypes: SelectOption[];
    events: string[];
    users: Option[];
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.activityLog.index().url,
    {
        search: filterValue(props.filters.search),
        subject_type: filterValue(props.filters.subject_type),
        subject_id: filterValue(props.filters.subject_id),
        event: filterValue(props.filters.event),
        user_id: filterValue(props.filters.user_id),
        date_from: filterValue(props.filters.date_from),
        date_to: filterValue(props.filters.date_to),
    },
);

const EVENTS: Record<string, { label: string; classes: string }> = {
    created: { label: 'Creó', classes: 'bg-[#E8F5EE] text-[#1F7A4D]' },
    updated: { label: 'Modificó', classes: 'bg-[#EAF1FB] text-[#2457A6]' },
    deleted: { label: 'Eliminó', classes: 'bg-[#F9ECEE] text-[#941B2B]' },
    restored: { label: 'Restauró', classes: 'bg-[#FDF4E3] text-[#8A5A00]' },
};

const eventOptions = props.events.map((value) => ({ value, label: EVENTS[value]?.label ?? value }));
const typeName = (key: string) => props.subjectTypes.find((t) => t.value === key)?.label ?? key;
const userName = (id: string) => props.users.find((u) => String(u.id) === id)?.name;

const expanded = ref(new Set<number>());
const toggle = (id: number): void => {
    if (expanded.value.has(id)) {
        expanded.value.delete(id);
    } else {
        expanded.value.add(id);
    }
};

/** Historial completo de un solo registro. */
const showRecord = (log: LogEntry): void => {
    filters.subject_type = log.subject_type;
    filters.subject_id = String(log.subject_id);
    filters.search = '';
    submit();
};

const display = (value: unknown): string => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (typeof value === 'boolean') {
        return value ? 'Sí' : 'No';
    }

    return typeof value === 'object' ? JSON.stringify(value) : String(value);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Historial de cambios', href: admin.activityLog.index() },
        ],
    },
});
</script>

<template>
    <Head title="Historial de cambios" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Historial de cambios"
            description="Quién creó, modificó o eliminó cada registro y cuándo."
            section="Configuración"
            :icon="FileClock"
        />

        <ActivityLogTabs active="changes" />

        <DataTable
            v-model:search="filters.search"
            :pagination="logs"
            :is-empty="logs.data.length === 0"
            :empty-colspan="5"
            search-placeholder="Buscar por nombre o folio del registro..."
            :has-filters="hasFilters"
            @search="submit"
            @clear-filters="clear"
        >
            <template #filters>
                <FilterSelect v-model="filters.subject_type" placeholder="Todos los registros" :options="subjectTypes" @change="filters.subject_id = ''; submit()" />
                <FilterSelect v-model="filters.event" placeholder="Todas las acciones" :options="eventOptions" @change="submit" />
                <FilterSelect v-model="filters.user_id" placeholder="Todos los usuarios" :options="recordOptions(users)" @change="submit" />
                <input v-model="filters.date_from" type="date" class="form-input h-[42px] w-auto" title="Desde" @change="submit" />
                <input v-model="filters.date_to" type="date" class="form-input h-[42px] w-auto" title="Hasta" @change="submit" />
            </template>

            <template #active-filters>
                <FilterChip v-if="filters.search">Búsqueda: {{ filters.search }}</FilterChip>
                <FilterChip v-if="filters.subject_type">Registro: {{ typeName(filters.subject_type) }}<template v-if="filters.subject_id"> #{{ filters.subject_id }}</template></FilterChip>
                <FilterChip v-if="filters.event">Acción: {{ EVENTS[filters.event]?.label ?? filters.event }}</FilterChip>
                <FilterChip v-if="filters.user_id">Usuario: {{ userName(filters.user_id) }}</FilterChip>
                <FilterChip v-if="filters.date_from">Desde: {{ filters.date_from }}</FilterChip>
                <FilterChip v-if="filters.date_to">Hasta: {{ filters.date_to }}</FilterChip>
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Fecha</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Usuario</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Acción</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Registro</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Cambios</th>
                </tr>
            </template>

            <template #table-body>
                <template v-for="log in logs.data" :key="log.id">
                    <tr class="transition-colors hover:bg-[#FAF3F4]">
                        <td class="whitespace-nowrap px-5 py-4 text-[#525960] sm:px-6">{{ formatDateTime(log.created_at) }}</td>
                        <td class="px-5 py-4 sm:px-6">
                            <p class="text-[#292D32]">{{ log.user?.name ?? 'Sistema' }}</p>
                            <p v-if="log.ip_address" class="text-xs text-[#858B93]">{{ log.ip_address }}</p>
                        </td>
                        <td class="px-5 py-4 sm:px-6">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="EVENTS[log.event]?.classes">
                                {{ EVENTS[log.event]?.label ?? log.event }}
                            </span>
                        </td>
                        <td class="px-5 py-4 sm:px-6">
                            <p class="font-semibold text-[#292D32]">{{ log.subject_label ?? `#${log.subject_id}` }}</p>
                            <button type="button" class="text-xs text-[#858B93] hover:text-[#941B2B] hover:underline" title="Ver todo el historial de este registro" @click="showRecord(log)">
                                {{ log.subject_name }} #{{ log.subject_id }}
                            </button>
                        </td>
                        <td class="px-5 py-4 text-right sm:px-6">
                            <button
                                v-if="log.changes"
                                type="button"
                                class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold text-[#941B2B] hover:bg-[#F9ECEE]"
                                :aria-expanded="expanded.has(log.id)"
                                @click="toggle(log.id)"
                            >
                                {{ Object.keys(log.changes).length }} campo(s)
                                <ChevronDown class="h-3.5 w-3.5 transition-transform" :class="{ 'rotate-180': expanded.has(log.id) }" />
                            </button>
                            <span v-else class="text-xs text-[#858B93]">—</span>
                        </td>
                    </tr>

                    <tr v-if="log.changes && expanded.has(log.id)" class="bg-[#F7F8FA]">
                        <td colspan="5" class="px-5 pb-4 pt-1 sm:px-6">
                            <table class="w-full table-fixed text-xs">
                                <thead class="text-left text-[#737982]">
                                    <tr>
                                        <th class="w-1/4 py-2 pr-3 font-semibold">Campo</th>
                                        <th v-if="log.event !== 'created'" class="py-2 pr-3 font-semibold">Antes</th>
                                        <th class="py-2 font-semibold">{{ log.event === 'created' ? 'Valor' : 'Después' }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E3E5E8]">
                                    <tr v-for="(pair, field) in log.changes" :key="field">
                                        <td class="py-2 pr-3 font-medium capitalize text-[#383D43]">{{ label(String(field)) }}</td>
                                        <td v-if="log.event !== 'created'" class="break-words py-2 pr-3 text-[#941B2B]">{{ display(pair[0]) }}</td>
                                        <td class="break-words py-2 text-[#1F7A4D]">{{ display(pair[1]) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </template>
            </template>

            <template #empty>
                <EmptyState
                    :icon="History"
                    title="Sin movimientos"
                    :description="hasFilters ? 'Prueba con otros filtros.' : 'Aún no hay cambios registrados.'"
                />
            </template>
        </DataTable>
    </div>
</template>
