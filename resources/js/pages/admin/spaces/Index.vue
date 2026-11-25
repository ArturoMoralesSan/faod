<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { DoorOpen, Plus } from 'lucide-vue-next';

import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterChip from '@/components/admin/FilterChip.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import TableActions from '@/components/admin/TableActions.vue';
import { filterValue, useIndexFilters } from '@/composables/useIndexFilters';
import { confirmDelete, enumOptions, label, recordOptions } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option, Paginated } from '@/types/admin';

interface Space {
    id: number;
    code: string;
    name: string;
    type: string;
    building: string | null;
    floor: string | null;
    capacity: number | null;
    is_active: boolean;
    clinic: Option | null;
}

const props = defineProps<{
    spaces: Paginated<Space>;
    filters: { search?: string; type?: string; clinic_id?: string };
    types: string[];
    clinics: Option[];
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.spaces.index().url,
    {
        search: filterValue(props.filters.search),
        type: filterValue(props.filters.type),
        clinic_id: filterValue(props.filters.clinic_id),
    },
);

const clinicName = (id: string) => props.clinics.find((c) => String(c.id) === id)?.name;

const destroy = (space: Space): void => {
    confirmDelete(
        '¿Eliminar espacio?',
        `Se eliminará "${space.name}". Los horarios que lo usan quedarán sin aula.`,
        admin.spaces.destroy(space.id).url,
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Espacios', href: admin.spaces.index() },
        ],
    },
});
</script>

<template>
    <Head title="Espacios" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Aulas y espacios"
            description="Aulas, laboratorios, consultorios y auditorios."
            section="Clínicas y espacios"
            :icon="DoorOpen"
            :action-icon="Plus"
            action-label="Nuevo espacio"
            :action-href="admin.spaces.create().url"
        />

        <DataTable
            v-model:search="filters.search"
            :pagination="spaces"
            :is-empty="spaces.data.length === 0"
            :empty-colspan="6"
            search-placeholder="Buscar por nombre o clave..."
            :has-filters="hasFilters"
            @search="submit"
            @clear-filters="clear"
        >
            <template #filters>
                <FilterSelect v-model="filters.type" placeholder="Todos los tipos" :options="enumOptions(types)" @change="submit" />
                <FilterSelect v-model="filters.clinic_id" placeholder="Todas las clínicas" :options="recordOptions(clinics)" @change="submit" />
            </template>

            <template #active-filters>
                <FilterChip v-if="filters.search">Búsqueda: {{ filters.search }}</FilterChip>
                <FilterChip v-if="filters.type">Tipo: {{ label(filters.type) }}</FilterChip>
                <FilterChip v-if="filters.clinic_id">Clínica: {{ clinicName(filters.clinic_id) }}</FilterChip>
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Espacio</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Tipo</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Ubicación</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Capacidad</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Estado</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Acciones</th>
                </tr>
            </template>

            <template #table-body>
                <tr
                    v-for="space in spaces.data"
                    :key="space.id"
                    class="transition-colors hover:bg-[#F4F5F6]"
                >
                    <td class="px-5 py-4 sm:px-6">
                        <p class="font-semibold text-[#292D32]">{{ space.name }}</p>
                        <p class="mt-0.5 text-xs text-[#858B93]">{{ space.code }}</p>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">
                        {{ label(space.type) }}
                        <p v-if="space.clinic" class="text-xs text-[#858B93]">{{ space.clinic.name }}</p>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">
                        {{ [space.building, space.floor ? `Planta ${space.floor}` : null].filter(Boolean).join(' · ') || '—' }}
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ space.capacity ?? '—' }}</td>
                    <td class="px-5 py-4 sm:px-6"><StatusBadge :value="space.is_active" /></td>

                    <TableActions
                        :view-url="admin.spaces.show(space.id).url"
                        :edit-url="admin.spaces.edit(space.id).url"
                        item-name="espacio"
                        @delete="destroy(space)"
                    />
                </tr>
            </template>

            <template #empty>
                <EmptyState
                    :icon="DoorOpen"
                    title="No se encontraron espacios"
                    :description="hasFilters ? 'Prueba con otros filtros.' : 'Todavía no hay espacios registrados.'"
                />
            </template>
        </DataTable>
    </div>
</template>
