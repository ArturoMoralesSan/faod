<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Library, Plus } from 'lucide-vue-next';

import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterChip from '@/components/admin/FilterChip.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import TableActions from '@/components/admin/TableActions.vue';
import { filterValue, useIndexFilters } from '@/composables/useIndexFilters';
import { confirmDelete, formatDate, recordOptions } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option, Paginated } from '@/types/admin';

interface Plan {
    id: number;
    name: string;
    version: string;
    valid_from: string | null;
    is_active: boolean;
    academic_program: Option;
    subjects_count: number;
}

const props = defineProps<{
    plans: Paginated<Plan>;
    filters: { academic_program_id?: string };
    programs: Option[];
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.studyPlans.index().url,
    { academic_program_id: filterValue(props.filters.academic_program_id) },
);

const programName = (id: string) => props.programs.find((p) => String(p.id) === id)?.name;

const destroy = (plan: Plan): void => {
    confirmDelete(
        '¿Eliminar plan académico?',
        `Se eliminará "${plan.name}" con sus módulos y asignaturas asignadas.`,
        admin.studyPlans.destroy(plan.id).url,
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Planes académicos', href: admin.studyPlans.index() },
        ],
    },
});
</script>

<template>
    <Head title="Planes académicos" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Planes académicos"
            description="Planes internos con sus asignaturas organizadas por semestre."
            section="Programas académicos"
            :icon="Library"
            :action-icon="Plus"
            action-label="Nuevo plan"
            :action-href="admin.studyPlans.create().url"
            action-permission="study-plans.create"
        />

        <DataTable
            :pagination="plans"
            :is-empty="plans.data.length === 0"
            :empty-colspan="5"
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
            </template>

            <template #active-filters>
                <FilterChip v-if="filters.academic_program_id">Programa: {{ programName(filters.academic_program_id) }}</FilterChip>
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Plan</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Vigente desde</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Asignaturas</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Estado</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Acciones</th>
                </tr>
            </template>

            <template #table-body>
                <tr
                    v-for="plan in plans.data"
                    :key="plan.id"
                    class="transition-colors hover:bg-[#F4F5F6]"
                >
                    <td class="px-5 py-4 sm:px-6">
                        <p class="font-semibold text-[#292D32]">{{ plan.name }} <span class="text-[#858B93]">v{{ plan.version }}</span></p>
                        <p class="mt-0.5 text-xs text-[#858B93]">{{ plan.academic_program.name }}</p>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ formatDate(plan.valid_from) }}</td>
                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ plan.subjects_count }}</td>
                    <td class="px-5 py-4 sm:px-6"><StatusBadge :value="plan.is_active" /></td>

                    <TableActions
                        permission="study-plans"
                        :view-url="admin.studyPlans.show(plan.id).url"
                        :edit-url="admin.studyPlans.edit(plan.id).url"
                        item-name="plan"
                        @delete="destroy(plan)"
                    />
                </tr>
            </template>

            <template #empty>
                <EmptyState
                    :icon="Library"
                    title="No se encontraron planes"
                    :description="hasFilters ? 'Prueba con otro programa.' : 'Todavía no hay planes registrados.'"
                />
            </template>
        </DataTable>
    </div>
</template>
