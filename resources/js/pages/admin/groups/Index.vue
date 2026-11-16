<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CalendarClock, Plus } from 'lucide-vue-next';

import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterChip from '@/components/admin/FilterChip.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import TableActions from '@/components/admin/TableActions.vue';
import { filterValue, useIndexFilters } from '@/composables/useIndexFilters';
import { confirmDelete } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option, Paginated } from '@/types/admin';

interface Group {
    id: number;
    code: string;
    period_number: number;
    capacity: number | null;
    cohort: { id: number; name: string; academic_program: { id: number; name: string; code: string } };
    academic_period: Option;
    students_count: number;
    class_sections_count: number;
}

const props = defineProps<{
    groups: Paginated<Group>;
    filters: { cohort_id?: string; academic_period_id?: number | string | null };
    periods: { id: number; name: string; is_current: boolean }[];
    cohorts: { id: number; name: string; academic_program: { id: number; code: string } | null }[];
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.groups.index().url,
    {
        academic_period_id: filterValue(props.filters.academic_period_id),
        cohort_id: filterValue(props.filters.cohort_id),
    },
);

const periodOptions = props.periods.map((period) => ({
    value: period.id,
    label: period.is_current ? `${period.name} (vigente)` : period.name,
}));

const cohortOptions = props.cohorts.map((cohort) => ({
    value: cohort.id,
    label: `${cohort.academic_program?.code ?? ''} · ${cohort.name}`,
}));

const destroy = (group: Group): void => {
    confirmDelete(
        '¿Eliminar grupo?',
        `Se eliminará el grupo "${group.code}" con sus materias y horarios.`,
        admin.groups.destroy(group.id).url,
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Grupos', href: admin.groups.index() },
        ],
    },
});
</script>

<template>
    <Head title="Grupos" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Grupos y horarios"
            description="Grupos por periodo con sus materias, docentes y horarios."
            section="Grupos y horarios"
            :icon="CalendarClock"
            :action-icon="Plus"
            action-label="Nuevo grupo"
            :action-href="admin.groups.create().url"
        />

        <DataTable
            :pagination="groups"
            :is-empty="groups.data.length === 0"
            :empty-colspan="6"
            :show-search="false"
            :has-filters="Boolean(filters.cohort_id)"
            @clear-filters="clear"
        >
            <template #filters>
                <FilterSelect v-model="filters.academic_period_id" placeholder="Periodo vigente" :options="periodOptions" @change="submit" />
                <FilterSelect v-model="filters.cohort_id" placeholder="Todas las generaciones" :options="cohortOptions" @change="submit" />
            </template>

            <template #active-filters>
                <FilterChip v-if="filters.cohort_id">
                    Generación: {{ cohortOptions.find((o) => String(o.value) === filters.cohort_id)?.label }}
                </FilterChip>
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Grupo</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Programa / generación</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Periodo</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Alumnos</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Materias</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Acciones</th>
                </tr>
            </template>

            <template #table-body>
                <tr
                    v-for="group in groups.data"
                    :key="group.id"
                    class="transition-colors hover:bg-[#FAF3F4]"
                >
                    <td class="px-5 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]">
                                <CalendarClock class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <p class="font-semibold text-[#292D32]">{{ group.code }}</p>
                                <p class="mt-0.5 text-xs text-[#858B93]">Semestre {{ group.period_number }}</p>
                            </div>
                        </div>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">
                        {{ group.cohort.academic_program.name }}
                        <p class="text-xs text-[#858B93]">Generación {{ group.cohort.name }}</p>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ group.academic_period.name }}</td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">
                        {{ group.students_count }}<template v-if="group.capacity"> / {{ group.capacity }}</template>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ group.class_sections_count }}</td>

                    <TableActions
                        :view-url="admin.groups.show(group.id).url"
                        :edit-url="admin.groups.edit(group.id).url"
                        item-name="grupo"
                        @delete="destroy(group)"
                    />
                </tr>
            </template>

            <template #empty>
                <EmptyState
                    :icon="CalendarClock"
                    title="No se encontraron grupos"
                    description="No hay grupos en el periodo seleccionado."
                />
            </template>
        </DataTable>
    </div>
</template>
