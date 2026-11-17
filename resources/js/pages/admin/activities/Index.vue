<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Presentation } from 'lucide-vue-next';

import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { filterValue, useIndexFilters } from '@/composables/useIndexFilters';
import { enumOptions, formatDate, formatMoney, fullName, label } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Paginated, PersonName } from '@/types/admin';

interface Activity {
    id: number;
    type: string;
    title: string;
    modality: string;
    starts_at: string;
    ends_at: string | null;
    capacity: number | null;
    cost: number | null;
    status: string;
    participants_count: number;
    coordinator: { people: PersonName | null } | null;
    space: { code: string; name: string } | null;
}

const props = defineProps<{
    activities: Paginated<Activity>;
    filters: { search?: string; type?: string; status?: string };
    types: string[];
    statuses: string[];
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.activities.index().url,
    {
        search: filterValue(props.filters.search),
        type: filterValue(props.filters.type),
        status: filterValue(props.filters.status),
    },
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Cursos y actividades', href: admin.activities.index() },
        ],
    },
});
</script>

<template>
    <Head title="Cursos y actividades" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Cursos y actividades académicas"
            description="Cursos, seminarios, congresos y talleres con sus inscritos y constancias."
            section="Académico"
            :icon="Presentation"
            :action-icon="Plus"
            action-label="Nueva actividad"
            :action-href="admin.activities.create().url"
        />

        <DataTable
            v-model:search="filters.search"
            :pagination="activities"
            :is-empty="activities.data.length === 0"
            :empty-colspan="4"
            search-placeholder="Buscar por título..."
            :has-filters="hasFilters"
            @search="submit"
            @clear-filters="clear"
        >
            <template #filters>
                <FilterSelect v-model="filters.type" placeholder="Todos los tipos" :options="enumOptions(types)" @change="submit" />
                <FilterSelect v-model="filters.status" placeholder="Todos los estados" :options="enumOptions(statuses)" @change="submit" />
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Actividad</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Fechas</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Inscritos</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Estado</th>
                </tr>
            </template>

            <template #table-body>
                <tr v-for="a in activities.data" :key="a.id" class="transition-colors hover:bg-[#FAF3F4]">
                    <td class="px-5 py-4 sm:px-6">
                        <Link :href="admin.activities.show(a.id).url" class="font-semibold text-[#292D32] hover:text-[#941B2B]">{{ a.title }}</Link>
                        <p class="text-xs text-[#858B93]">
                            {{ [label(a.type), label(a.modality), a.coordinator ? fullName(a.coordinator.people) : null, a.cost ? formatMoney(a.cost) : null].filter(Boolean).join(' · ') }}
                        </p>
                    </td>
                    <td class="px-5 py-4 text-[#525960] sm:px-6">
                        {{ formatDate(a.starts_at) }}<template v-if="a.ends_at && a.ends_at !== a.starts_at"> – {{ formatDate(a.ends_at) }}</template>
                        <p v-if="a.space" class="text-xs text-[#858B93]">{{ a.space.name }}</p>
                    </td>
                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ a.participants_count }}<template v-if="a.capacity"> / {{ a.capacity }}</template></td>
                    <td class="px-5 py-4 sm:px-6"><StatusBadge :value="a.status" /></td>
                </tr>
            </template>

            <template #empty>
                <EmptyState :icon="Presentation" title="Sin actividades" :description="hasFilters ? 'Prueba con otros filtros.' : 'Registra el primer curso o actividad.'" />
            </template>
        </DataTable>
    </div>
</template>
