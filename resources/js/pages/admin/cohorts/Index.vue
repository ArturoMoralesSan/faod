<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Plus, UsersRound } from 'lucide-vue-next';

import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterChip from '@/components/admin/FilterChip.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import TableActions from '@/components/admin/TableActions.vue';
import { filterValue, useIndexFilters } from '@/composables/useIndexFilters';
import { confirmDelete, enumOptions, formatDate, label, recordOptions } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option, Paginated } from '@/types/admin';

interface Cohort {
    id: number;
    name: string;
    status: string;
    starts_at: string | null;
    ends_at: string | null;
    academic_program: Option;
    groups_count: number;
    active_students_count: number;
}

const props = defineProps<{
    cohorts: Paginated<Cohort>;
    filters: { academic_program_id?: string; status?: string };
    statuses: string[];
    programs: Option[];
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.cohorts.index().url,
    {
        academic_program_id: filterValue(props.filters.academic_program_id),
        status: filterValue(props.filters.status),
    },
);

const programName = (id: string) => props.programs.find((p) => String(p.id) === id)?.name;

const destroy = (cohort: Cohort): void => {
    confirmDelete(
        '¿Eliminar generación?',
        `Se eliminará la generación "${cohort.name}" con sus grupos.`,
        admin.cohorts.destroy(cohort.id).url,
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Generaciones', href: admin.cohorts.index() },
        ],
    },
});
</script>

<template>
    <Head title="Generaciones" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Generaciones"
            description="Generaciones de alumnos por programa académico."
            section="Programas académicos"
            :icon="UsersRound"
            :action-icon="Plus"
            action-label="Nueva generación"
            :action-href="admin.cohorts.create().url"
        />

        <DataTable
            :pagination="cohorts"
            :is-empty="cohorts.data.length === 0"
            :empty-colspan="6"
            :show-search="false"
            :has-filters="hasFilters"
            @clear-filters="clear"
        >
            <template #filters>
                <FilterSelect
                    v-model="filters.academic_program_id"
                    placeholder="Todos los programas"
                    :options="recordOptions(programs, true)"
                    @change="submit"
                />
                <FilterSelect
                    v-model="filters.status"
                    placeholder="Cualquier estado"
                    :options="enumOptions(statuses)"
                    @change="submit"
                />
            </template>

            <template #active-filters>
                <FilterChip v-if="filters.academic_program_id">Programa: {{ programName(filters.academic_program_id) }}</FilterChip>
                <FilterChip v-if="filters.status">Estado: {{ label(filters.status) }}</FilterChip>
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Generación</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Vigencia</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Alumnos activos</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Grupos</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Estado</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Acciones</th>
                </tr>
            </template>

            <template #table-body>
                <tr
                    v-for="cohort in cohorts.data"
                    :key="cohort.id"
                    class="transition-colors hover:bg-[#FAF3F4]"
                >
                    <td class="px-5 py-4 sm:px-6">
                        <p class="font-semibold text-[#292D32]">{{ cohort.name }}</p>
                        <p class="mt-0.5 text-xs text-[#858B93]">{{ cohort.academic_program.name }}</p>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">
                        {{ formatDate(cohort.starts_at) }} – {{ formatDate(cohort.ends_at) }}
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ cohort.active_students_count }}</td>
                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ cohort.groups_count }}</td>
                    <td class="px-5 py-4 sm:px-6"><StatusBadge :value="cohort.status" /></td>

                    <TableActions
                        :view-url="admin.cohorts.show(cohort.id).url"
                        :edit-url="admin.cohorts.edit(cohort.id).url"
                        item-name="generación"
                        @delete="destroy(cohort)"
                    />
                </tr>
            </template>

            <template #empty>
                <EmptyState
                    :icon="UsersRound"
                    title="No se encontraron generaciones"
                    :description="hasFilters ? 'Prueba con otros filtros.' : 'Todavía no hay generaciones registradas.'"
                />
            </template>
        </DataTable>
    </div>
</template>
