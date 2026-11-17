<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Plus, TriangleAlert } from 'lucide-vue-next';

import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterChip from '@/components/admin/FilterChip.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import SeverityBadge from '@/components/admin/SeverityBadge.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import TableActions from '@/components/admin/TableActions.vue';
import { filterValue, useIndexFilters } from '@/composables/useIndexFilters';
import { confirmDelete, enumOptions, formatDateTime, label, recordOptions } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option, Paginated } from '@/types/admin';

interface Incident {
    id: number;
    title: string;
    type: string;
    severity: string;
    status: string;
    occurred_at: string;
    clinic: Option;
    space: { code: string; name: string } | null;
    equipment: { code: string; name: string } | null;
    reporter: { name: string } | null;
}

const props = defineProps<{
    incidents: Paginated<Incident>;
    filters: { search?: string; clinic_id?: string; status?: string; severity?: string; type?: string };
    clinics: Option[];
    types: string[];
    severities: string[];
    statuses: string[];
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.clinicIncidents.index().url,
    {
        search: filterValue(props.filters.search),
        clinic_id: filterValue(props.filters.clinic_id),
        status: filterValue(props.filters.status),
        severity: filterValue(props.filters.severity),
        type: filterValue(props.filters.type),
    },
);

const clinicName = (id: string) => props.clinics.find((c) => String(c.id) === id)?.name;

const destroy = (incident: Incident): void => {
    confirmDelete(
        '¿Eliminar incidencia?',
        `Se eliminará "${incident.title}".`,
        admin.clinicIncidents.destroy(incident.id).url,
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Incidencias', href: admin.clinicIncidents.index() },
        ],
    },
});
</script>

<template>
    <Head title="Incidencias" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Incidencias de clínicas"
            description="Fallas de equipo, instalaciones, bioseguridad y otros eventos."
            section="Clínicas"
            :icon="TriangleAlert"
            :action-icon="Plus"
            action-label="Reportar incidencia"
            :action-href="admin.clinicIncidents.create().url"
        />

        <DataTable
            v-model:search="filters.search"
            :pagination="incidents"
            :is-empty="incidents.data.length === 0"
            :empty-colspan="6"
            search-placeholder="Buscar por título..."
            :has-filters="hasFilters"
            @search="submit"
            @clear-filters="clear"
        >
            <template #filters>
                <FilterSelect v-model="filters.clinic_id" placeholder="Todas las clínicas" :options="recordOptions(clinics)" @change="submit" />
                <FilterSelect v-model="filters.status" placeholder="Todos los estados" :options="enumOptions(statuses)" @change="submit" />
                <FilterSelect v-model="filters.severity" placeholder="Toda gravedad" :options="enumOptions(severities)" @change="submit" />
                <FilterSelect v-model="filters.type" placeholder="Todos los tipos" :options="enumOptions(types)" @change="submit" />
            </template>

            <template #active-filters>
                <FilterChip v-if="filters.search">Búsqueda: {{ filters.search }}</FilterChip>
                <FilterChip v-if="filters.clinic_id">Clínica: {{ clinicName(filters.clinic_id) }}</FilterChip>
                <FilterChip v-if="filters.status">Estado: {{ label(filters.status) }}</FilterChip>
                <FilterChip v-if="filters.severity">Gravedad: {{ label(filters.severity) }}</FilterChip>
                <FilterChip v-if="filters.type">Tipo: {{ label(filters.type) }}</FilterChip>
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Incidencia</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Clínica</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Gravedad</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Estado</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Fecha</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Acciones</th>
                </tr>
            </template>

            <template #table-body>
                <tr v-for="incident in incidents.data" :key="incident.id" class="transition-colors hover:bg-[#FAF3F4]">
                    <td class="px-5 py-4 sm:px-6">
                        <p class="font-semibold text-[#292D32]">{{ incident.title }}</p>
                        <p class="mt-0.5 text-xs text-[#858B93]">
                            {{ [label(incident.type), incident.equipment?.name, incident.reporter ? `Reportó ${incident.reporter.name}` : null].filter(Boolean).join(' · ') }}
                        </p>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">
                        {{ incident.clinic.name }}
                        <p v-if="incident.space" class="text-xs text-[#858B93]">{{ incident.space.name }}</p>
                    </td>

                    <td class="px-5 py-4 sm:px-6"><SeverityBadge :value="incident.severity" /></td>
                    <td class="px-5 py-4 sm:px-6"><StatusBadge :value="incident.status" /></td>
                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ formatDateTime(incident.occurred_at) }}</td>

                    <TableActions
                        :view-url="admin.clinicIncidents.show(incident.id).url"
                        :edit-url="admin.clinicIncidents.edit(incident.id).url"
                        item-name="incidencia"
                        @delete="destroy(incident)"
                    />
                </tr>
            </template>

            <template #empty>
                <EmptyState
                    :icon="TriangleAlert"
                    title="No se encontraron incidencias"
                    :description="hasFilters ? 'Prueba con otros filtros.' : 'No hay incidencias registradas.'"
                />
            </template>
        </DataTable>
    </div>
</template>
