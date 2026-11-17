<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CalendarCheck, Plus } from 'lucide-vue-next';

import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterChip from '@/components/admin/FilterChip.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import TableActions from '@/components/admin/TableActions.vue';
import { filterValue, useIndexFilters } from '@/composables/useIndexFilters';
import { confirmDelete, enumOptions, formatDate, formatTime, fullName, label, recordOptions } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option, Paginated, PersonName } from '@/types/admin';

interface Session {
    id: number;
    date: string;
    start_time: string;
    end_time: string;
    status: string;
    topic: string | null;
    students_count: number;
    clinic: Option;
    space: { code: string; name: string } | null;
    group: { code: string } | null;
    subject: { code: string; name: string } | null;
    supervisor: { people: PersonName | null } | null;
}

const props = defineProps<{
    sessions: Paginated<Session>;
    filters: { search?: string; clinic_id?: string; status?: string; date_from?: string; date_to?: string };
    clinics: Option[];
    statuses: string[];
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.clinicalSessions.index().url,
    {
        search: filterValue(props.filters.search),
        clinic_id: filterValue(props.filters.clinic_id),
        status: filterValue(props.filters.status),
        date_from: filterValue(props.filters.date_from),
        date_to: filterValue(props.filters.date_to),
    },
);

// Mismo aspecto que FilterSelect.
const dateInputClass =
    'h-[42px] rounded-xl border border-[#E3E5E8] bg-white px-3 text-sm text-[#292D32] outline-none hover:border-[#E8C7CC] focus:border-[#941B2B] focus:ring-2 focus:ring-[#941B2B]/10';

const clinicName =(id: string) => props.clinics.find((c) => String(c.id) === id)?.name;

const destroy = (session: Session): void => {
    confirmDelete(
        '¿Eliminar sesión clínica?',
        `Se eliminará la sesión del ${formatDate(session.date)} con su lista de asistencia.`,
        admin.clinicalSessions.destroy(session.id).url,
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Sesiones clínicas', href: admin.clinicalSessions.index() },
        ],
    },
});
</script>

<template>
    <Head title="Sesiones clínicas" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Sesiones clínicas"
            description="Prácticas supervisadas por fecha, consultorio y grupo."
            section="Clínicas"
            :icon="CalendarCheck"
            :action-icon="Plus"
            action-label="Programar sesión"
            :action-href="admin.clinicalSessions.create().url"
        />

        <DataTable
            v-model:search="filters.search"
            :pagination="sessions"
            :is-empty="sessions.data.length === 0"
            :empty-colspan="6"
            search-placeholder="Buscar por tema..."
            :has-filters="hasFilters"
            @search="submit"
            @clear-filters="clear"
        >
            <template #filters>
                <FilterSelect v-model="filters.clinic_id" placeholder="Todas las clínicas" :options="recordOptions(clinics)" @change="submit" />
                <FilterSelect v-model="filters.status" placeholder="Todos los estados" :options="enumOptions(statuses)" @change="submit" />
                <input v-model="filters.date_from" type="date" :class="dateInputClass" title="Desde" @change="submit" />
                <input v-model="filters.date_to" type="date" :class="dateInputClass" title="Hasta" @change="submit" />
            </template>

            <template #active-filters>
                <FilterChip v-if="filters.search">Búsqueda: {{ filters.search }}</FilterChip>
                <FilterChip v-if="filters.clinic_id">Clínica: {{ clinicName(filters.clinic_id) }}</FilterChip>
                <FilterChip v-if="filters.status">Estado: {{ label(filters.status) }}</FilterChip>
                <FilterChip v-if="filters.date_from">Desde: {{ formatDate(filters.date_from) }}</FilterChip>
                <FilterChip v-if="filters.date_to">Hasta: {{ formatDate(filters.date_to) }}</FilterChip>
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Fecha</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Clínica</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Grupo / actividad</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Supervisor</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Estado</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Acciones</th>
                </tr>
            </template>

            <template #table-body>
                <tr v-for="session in sessions.data" :key="session.id" class="transition-colors hover:bg-[#FAF3F4]">
                    <td class="px-5 py-4 sm:px-6">
                        <p class="font-semibold text-[#292D32]">{{ formatDate(session.date) }}</p>
                        <p class="mt-0.5 text-xs text-[#858B93]">{{ formatTime(session.start_time) }} – {{ formatTime(session.end_time) }}</p>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">
                        {{ session.clinic.name }}
                        <p v-if="session.space" class="text-xs text-[#858B93]">{{ session.space.name }}</p>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">
                        {{ session.group ? `Grupo ${session.group.code}` : '—' }}
                        <p class="text-xs text-[#858B93]">
                            {{ [session.subject?.name, session.topic].filter(Boolean).join(' · ') }}
                            <template v-if="session.students_count"> · {{ session.students_count }} alumnos</template>
                        </p>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ session.supervisor ? fullName(session.supervisor.people) : '—' }}</td>
                    <td class="px-5 py-4 sm:px-6"><StatusBadge :value="session.status" /></td>

                    <TableActions
                        :view-url="admin.clinicalSessions.show(session.id).url"
                        :edit-url="admin.clinicalSessions.edit(session.id).url"
                        item-name="sesión"
                        @delete="destroy(session)"
                    />
                </tr>
            </template>

            <template #empty>
                <EmptyState
                    :icon="CalendarCheck"
                    title="No se encontraron sesiones"
                    :description="hasFilters ? 'Prueba con otros filtros.' : 'Todavía no hay sesiones clínicas programadas.'"
                />
            </template>
        </DataTable>
    </div>
</template>
