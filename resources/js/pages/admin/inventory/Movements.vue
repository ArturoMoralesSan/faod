<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ScrollText } from 'lucide-vue-next';

import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import MovementTable from '@/components/admin/MovementTable.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { filterValue, useIndexFilters } from '@/composables/useIndexFilters';
import { enumOptions, recordOptions } from '@/lib/admin';
import admin from '@/routes/admin';
import type { MovementRow, Option, Paginated, SelectOption } from '@/types/admin';

const props = defineProps<{
    movements: Paginated<MovementRow>;
    filters: { inventory_item_id?: string; warehouse_id?: string; type?: string; student_profile_id?: string; date_from?: string; date_to?: string };
    students: SelectOption[];
    types: string[];
    items: Option[];
    warehouses: Option[];
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.inventory.movements.index().url,
    {
        inventory_item_id: filterValue(props.filters.inventory_item_id),
        warehouse_id: filterValue(props.filters.warehouse_id),
        type: filterValue(props.filters.type),
        student_profile_id: filterValue(props.filters.student_profile_id),
        date_from: filterValue(props.filters.date_from),
        date_to: filterValue(props.filters.date_to),
    },
);

const dateClass = 'h-[42px] rounded-xl border border-[#E3E5E8] bg-white px-3 text-sm';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Inventario', href: admin.inventory.index() },
            { title: 'Movimientos', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Movimientos de inventario" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader title="Movimientos (kárdex)" description="Entradas, salidas, consumos, ajustes y traspasos." section="Inventario" :icon="ScrollText" />

        <DataTable :pagination="movements" :is-empty="movements.data.length === 0" :empty-colspan="1" :show-search="false" :has-filters="hasFilters" @clear-filters="clear">
            <template #filters>
                <FilterSelect v-model="filters.inventory_item_id" placeholder="Todos los artículos" :options="recordOptions(items, true)" @change="submit" />
                <FilterSelect v-model="filters.warehouse_id" placeholder="Todos los almacenes" :options="recordOptions(warehouses)" @change="submit" />
                <FilterSelect v-model="filters.type" placeholder="Todos los tipos" :options="enumOptions(types)" @change="submit" />
                <FilterSelect v-model="filters.student_profile_id" placeholder="Todos los alumnos" :options="students" @change="submit" />
                <input v-model="filters.date_from" type="date" :class="dateClass" title="Desde" @change="submit" />
                <input v-model="filters.date_to" type="date" :class="dateClass" title="Hasta" @change="submit" />
            </template>

            <template #table-body>
                <tr>
                    <td class="p-0"><MovementTable :movements="movements.data" show-item /></td>
                </tr>
            </template>

            <template #empty>
                <EmptyState :icon="ScrollText" title="Sin movimientos" description="No hay movimientos con esos filtros." />
            </template>
        </DataTable>
    </div>
</template>
