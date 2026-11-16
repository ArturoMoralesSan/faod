<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Plus, Stethoscope } from 'lucide-vue-next';

import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterChip from '@/components/admin/FilterChip.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import TableActions from '@/components/admin/TableActions.vue';
import { filterValue, useIndexFilters } from '@/composables/useIndexFilters';
import { confirmDelete } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Paginated } from '@/types/admin';

interface Specialty {
    id: number;
    code: string;
    name: string;
    is_active: boolean;
    academic_programs_count: number;
    teachers_count: number;
}

const props = defineProps<{
    specialties: Paginated<Specialty>;
    filters: { search?: string };
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.specialties.index().url,
    { search: filterValue(props.filters.search) },
);

const destroy = (specialty: Specialty): void => {
    confirmDelete(
        '¿Eliminar especialidad?',
        `Se eliminará "${specialty.name}". Esta acción no se puede deshacer.`,
        admin.specialties.destroy(specialty.id).url,
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Especialidades', href: admin.specialties.index() },
        ],
    },
});
</script>

<template>
    <Head title="Especialidades" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Especialidades"
            description="Especialidades odontológicas de la facultad."
            section="Programas académicos"
            :icon="Stethoscope"
            :action-icon="Plus"
            action-label="Nueva especialidad"
            :action-href="admin.specialties.create().url"
        />

        <DataTable
            v-model:search="filters.search"
            :pagination="specialties"
            :is-empty="specialties.data.length === 0"
            :empty-colspan="5"
            search-placeholder="Buscar especialidad..."
            :has-filters="hasFilters"
            @search="submit"
            @clear-filters="clear"
        >
            <template #active-filters>
                <FilterChip v-if="filters.search">Búsqueda: {{ filters.search }}</FilterChip>
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Especialidad</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Programas</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Docentes</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Estado</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Acciones</th>
                </tr>
            </template>

            <template #table-body>
                <tr
                    v-for="specialty in specialties.data"
                    :key="specialty.id"
                    class="transition-colors hover:bg-[#FAF3F4]"
                >
                    <td class="px-5 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]">
                                <Stethoscope class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <p class="font-semibold text-[#292D32]">{{ specialty.name }}</p>
                                <p class="mt-0.5 text-xs text-[#858B93]">{{ specialty.code }}</p>
                            </div>
                        </div>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ specialty.academic_programs_count }}</td>
                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ specialty.teachers_count }}</td>
                    <td class="px-5 py-4 sm:px-6"><StatusBadge :value="specialty.is_active" /></td>

                    <TableActions
                        :edit-url="admin.specialties.edit(specialty.id).url"
                        :show-view="false"
                        item-name="especialidad"
                        @delete="destroy(specialty)"
                    />
                </tr>
            </template>

            <template #empty>
                <EmptyState
                    :icon="Stethoscope"
                    title="No se encontraron especialidades"
                    :description="hasFilters ? 'Prueba con otro término de búsqueda.' : 'Todavía no hay especialidades registradas.'"
                />
            </template>
        </DataTable>
    </div>
</template>
