<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ListTree, Lock, Plus } from 'lucide-vue-next';

import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterChip from '@/components/admin/FilterChip.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import TableActions from '@/components/admin/TableActions.vue';
import { filterValue, useIndexFilters } from '@/composables/useIndexFilters';
import { confirmDelete } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Paginated } from '@/types/admin';

interface Catalog {
    id: number;
    code: string;
    name: string;
    description: string | null;
    is_system: boolean;
    items_count: number;
}

const props = defineProps<{
    catalogs: Paginated<Catalog>;
    filters: { search?: string };
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.catalogs.index().url,
    { search: filterValue(props.filters.search) },
);

const destroy = (catalog: Catalog): void => {
    confirmDelete(
        '¿Eliminar catálogo?',
        `Se eliminará "${catalog.name}" con todas sus opciones.`,
        admin.catalogs.destroy(catalog.id).url,
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Catálogos', href: admin.catalogs.index() },
        ],
    },
});
</script>

<template>
    <Head title="Catálogos" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Catálogos generales"
            description="Listas de opciones que se usan en los formularios del sistema."
            section="Administración"
            :icon="ListTree"
            :action-icon="Plus"
            action-label="Nuevo catálogo"
            :action-href="admin.catalogs.create().url"
        />

        <DataTable
            v-model:search="filters.search"
            :pagination="catalogs"
            :is-empty="catalogs.data.length === 0"
            :empty-colspan="4"
            search-placeholder="Buscar catálogo..."
            :has-filters="hasFilters"
            @search="submit"
            @clear-filters="clear"
        >
            <template #active-filters>
                <FilterChip v-if="filters.search">Búsqueda: {{ filters.search }}</FilterChip>
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Catálogo</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Código</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Opciones</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Acciones</th>
                </tr>
            </template>

            <template #table-body>
                <tr
                    v-for="catalog in catalogs.data"
                    :key="catalog.id"
                    class="transition-colors hover:bg-[#FAF3F4]"
                >
                    <td class="px-5 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]">
                                <ListTree class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <p class="flex items-center gap-1.5 font-semibold text-[#292D32]">
                                    {{ catalog.name }}
                                    <Lock v-if="catalog.is_system" class="h-3.5 w-3.5 text-[#858B93]" />
                                </p>
                                <p v-if="catalog.description" class="mt-0.5 line-clamp-1 text-xs text-[#858B93]">
                                    {{ catalog.description }}
                                </p>
                            </div>
                        </div>
                    </td>

                    <td class="px-5 py-4 font-mono text-xs text-[#525960] sm:px-6">{{ catalog.code }}</td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ catalog.items_count }}</td>

                    <TableActions
                        :view-url="admin.catalogs.show(catalog.id).url"
                        :edit-url="admin.catalogs.edit(catalog.id).url"
                        :show-delete="!catalog.is_system"
                        item-name="catálogo"
                        @delete="destroy(catalog)"
                    />
                </tr>
            </template>

            <template #empty>
                <EmptyState
                    :icon="ListTree"
                    title="No se encontraron catálogos"
                    :description="hasFilters ? 'Prueba con otro término de búsqueda.' : 'Todavía no hay catálogos registrados.'"
                />
            </template>
        </DataTable>
    </div>
</template>
