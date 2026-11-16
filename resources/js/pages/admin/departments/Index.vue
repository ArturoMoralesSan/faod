<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Building2, Plus } from 'lucide-vue-next';

import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterChip from '@/components/admin/FilterChip.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import TableActions from '@/components/admin/TableActions.vue';
import { filterValue, useIndexFilters } from '@/composables/useIndexFilters';
import { confirmDelete, enumOptions, fullName, label } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Paginated, PersonName } from '@/types/admin';

interface Department {
    id: number;
    code: string | null;
    name: string;
    type: string;
    is_active: boolean;
    parent: { id: number; name: string } | null;
    head: PersonName | null;
    children_count: number;
    staff_count: number;
}

const props = defineProps<{
    departments: Paginated<Department>;
    filters: { search?: string; type?: string };
    types: string[];
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.departments.index().url,
    {
        search: filterValue(props.filters.search),
        type: filterValue(props.filters.type),
    },
);

const destroy = (department: Department): void => {
    confirmDelete(
        '¿Eliminar departamento?',
        `Se eliminará "${department.name}". Esta acción no se puede deshacer.`,
        admin.departments.destroy(department.id).url,
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Departamentos', href: admin.departments.index() },
        ],
    },
});
</script>

<template>
    <Head title="Departamentos" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Departamentos y coordinaciones"
            description="Administra la estructura organizacional de la facultad."
            section="Administración"
            :icon="Building2"
            :action-icon="Plus"
            action-label="Nuevo departamento"
            :action-href="admin.departments.create().url"
        />

        <DataTable
            v-model:search="filters.search"
            :pagination="departments"
            :is-empty="departments.data.length === 0"
            :empty-colspan="6"
            search-placeholder="Buscar por nombre o clave..."
            :has-filters="hasFilters"
            @search="submit"
            @clear-filters="clear"
        >
            <template #filters>
                <FilterSelect
                    v-model="filters.type"
                    placeholder="Todos los tipos"
                    :options="enumOptions(types)"
                    @change="submit"
                />
            </template>

            <template #active-filters>
                <FilterChip v-if="filters.search">Búsqueda: {{ filters.search }}</FilterChip>
                <FilterChip v-if="filters.type">Tipo: {{ label(filters.type) }}</FilterChip>
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Departamento</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Tipo</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Responsable</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Personal</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Estado</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Acciones</th>
                </tr>
            </template>

            <template #table-body>
                <tr
                    v-for="department in departments.data"
                    :key="department.id"
                    class="transition-colors hover:bg-[#FAF3F4]"
                >
                    <td class="px-5 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]">
                                <Building2 class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <p class="font-semibold text-[#292D32]">{{ department.name }}</p>
                                <p class="mt-0.5 text-xs text-[#858B93]">
                                    {{ department.code ?? 'Sin clave' }}
                                    <template v-if="department.parent"> · {{ department.parent.name }}</template>
                                </p>
                            </div>
                        </div>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ label(department.type) }}</td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">
                        {{ department.head ? fullName(department.head) : '—' }}
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ department.staff_count }}</td>

                    <td class="px-5 py-4 sm:px-6">
                        <StatusBadge :value="department.is_active" />
                    </td>

                    <TableActions
                        :edit-url="admin.departments.edit(department.id).url"
                        :show-view="false"
                        item-name="departamento"
                        @delete="destroy(department)"
                    />
                </tr>
            </template>

            <template #empty>
                <EmptyState
                    :icon="Building2"
                    title="No se encontraron departamentos"
                    :description="hasFilters ? 'Prueba con otros filtros.' : 'Todavía no hay departamentos registrados.'"
                />
            </template>
        </DataTable>
    </div>
</template>
