<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarX, Package, PackageMinus, Plus, TriangleAlert } from 'lucide-vue-next';
import { ref } from 'vue';

import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterChip from '@/components/admin/FilterChip.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import InventoryItemDialog from '@/components/admin/InventoryItemDialog.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { filterValue, useIndexFilters } from '@/composables/useIndexFilters';
import { enumOptions, formatQty, label, recordOptions } from '@/lib/admin';
import admin from '@/routes/admin';
import type { InventoryItemData, Option, Paginated } from '@/types/admin';

interface Item extends InventoryItemData {
    stock: number | string | null;
    expiring_lots_count: number;
    default_supplier: Option | null;
}

const props = defineProps<{
    items: Paginated<Item>;
    filters: { search?: string; category?: string; kind?: string; warehouse_id?: string; alert?: string };
    alerts: { low: number; expiring: number; expired: number };
    warehouses: Option[];
    suppliers: Option[];
    categories: string[];
    kinds: string[];
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.inventory.index().url,
    {
        search: filterValue(props.filters.search),
        category: filterValue(props.filters.category),
        kind: filterValue(props.filters.kind),
        warehouse_id: filterValue(props.filters.warehouse_id),
        alert: filterValue(props.filters.alert),
    },
);

const setAlert = (alert: string): void => {
    filters.alert = filters.alert === alert ? '' : alert;
    submit();
};

const isLow = (item: Item) => item.minimum_stock > 0 && Number(item.stock ?? 0) < item.minimum_stock;

const dialogOpen = ref(false);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Inventario', href: admin.inventory.index() },
        ],
    },
});
</script>

<template>
    <Head title="Inventario" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Artículos y existencias"
            description="Materiales, consumibles e instrumental en los almacenes."
            section="Inventario"
            :icon="Package"
            :action-icon="Plus"
            action-label="Nuevo artículo"
            @action="dialogOpen = true"
        />

        <!-- Alertas -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <button
                type="button"
                :class="['flex items-center gap-3 rounded-2xl border bg-white p-4 text-left transition', filters.alert === 'low' ? 'border-[#941B2B]' : 'border-[#E3E5E8] hover:border-[#B8BEC6]']"
                @click="setAlert('low')"
            >
                <PackageMinus :class="['h-6 w-6', alerts.low ? 'text-[#941B2B]' : 'text-[#858B93]']" />
                <div>
                    <p class="text-xl font-bold text-[#292D32]">{{ alerts.low }}</p>
                    <p class="text-xs text-[#737982]">Bajo el mínimo</p>
                </div>
            </button>
            <button
                type="button"
                :class="['flex items-center gap-3 rounded-2xl border bg-white p-4 text-left transition', filters.alert === 'expiring' ? 'border-[#941B2B]' : 'border-[#E3E5E8] hover:border-[#B8BEC6]']"
                @click="setAlert('expiring')"
            >
                <TriangleAlert :class="['h-6 w-6', alerts.expiring ? 'text-[#A64B00]' : 'text-[#858B93]']" />
                <div>
                    <p class="text-xl font-bold text-[#292D32]">{{ alerts.expiring }}</p>
                    <p class="text-xs text-[#737982]">Lotes por caducar (30 días)</p>
                </div>
            </button>
            <div class="flex items-center gap-3 rounded-2xl border border-[#E3E5E8] bg-white p-4">
                <CalendarX :class="['h-6 w-6', alerts.expired ? 'text-[#941B2B]' : 'text-[#858B93]']" />
                <div>
                    <p class="text-xl font-bold text-[#292D32]">{{ alerts.expired }}</p>
                    <p class="text-xs text-[#737982]">Lotes caducados con existencia</p>
                </div>
            </div>
        </div>

        <DataTable
            v-model:search="filters.search"
            :pagination="items"
            :is-empty="items.data.length === 0"
            :empty-colspan="5"
            search-placeholder="Buscar por clave o nombre..."
            :has-filters="hasFilters"
            @search="submit"
            @clear-filters="clear"
        >
            <template #filters>
                <FilterSelect v-model="filters.warehouse_id" placeholder="Todos los almacenes" :options="recordOptions(warehouses)" @change="submit" />
                <FilterSelect v-model="filters.category" placeholder="Todas las categorías" :options="categories.map((c) => ({ value: c, label: c }))" @change="submit" />
                <FilterSelect v-model="filters.kind" placeholder="Material, instrumental y equipo" :options="enumOptions(kinds)" @change="submit" />
            </template>

            <template #active-filters>
                <FilterChip v-if="filters.search">Búsqueda: {{ filters.search }}</FilterChip>
                <FilterChip v-if="filters.alert === 'low'">Bajo el mínimo</FilterChip>
                <FilterChip v-if="filters.alert === 'expiring'">Por caducar</FilterChip>
                <FilterChip v-if="filters.category">Categoría: {{ filters.category }}</FilterChip>
                <FilterChip v-if="filters.kind">Tipo: {{ label(filters.kind) }}</FilterChip>
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Artículo</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Categoría</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Existencia</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Mínimo</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Estado</th>
                </tr>
            </template>

            <template #table-body>
                <tr v-for="item in items.data" :key="item.id" class="transition-colors hover:bg-[#F4F5F6]">
                    <td class="px-5 py-4 sm:px-6">
                        <Link :href="admin.inventory.show(item.id).url" class="font-semibold text-[#292D32] hover:text-[#292D32]">{{ item.name }}</Link>
                        <p class="mt-0.5 text-xs text-[#858B93]">
                            {{ item.code }}<template v-if="item.tracks_expiration"> · con caducidad</template>
                            <span v-if="item.expiring_lots_count" class="font-semibold text-[#A64B00]"> · {{ item.expiring_lots_count }} lote(s) por caducar</span>
                        </p>
                    </td>
                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ item.category ?? '—' }}</td>
                    <td :class="['px-5 py-4 text-right font-semibold sm:px-6', isLow(item) ? 'text-[#941B2B]' : 'text-[#292D32]']">
                        {{ formatQty(item.stock) }} {{ item.unit }}
                    </td>
                    <td class="px-5 py-4 text-right text-[#525960] sm:px-6">{{ formatQty(item.minimum_stock) }}</td>
                    <td class="px-5 py-4 sm:px-6"><StatusBadge :value="item.is_active" /></td>
                </tr>
            </template>

            <template #empty>
                <EmptyState :icon="Package" title="Sin artículos" :description="hasFilters ? 'Prueba con otros filtros.' : 'Registra el primer artículo del inventario.'" />
            </template>
        </DataTable>

        <InventoryItemDialog v-model:open="dialogOpen" :item="null" :suppliers="suppliers" :categories="categories" :kinds="kinds" />
    </div>
</template>
