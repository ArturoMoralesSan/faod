<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Hospital, Plus } from 'lucide-vue-next';

import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterChip from '@/components/admin/FilterChip.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import TableActions from '@/components/admin/TableActions.vue';
import { filterValue, useIndexFilters } from '@/composables/useIndexFilters';
import { confirmDelete } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option, Paginated } from '@/types/admin';

interface Clinic {
    id: number;
    code: string;
    name: string;
    location: string | null;
    is_active: boolean;
    department: Option | null;
    spaces_count: number;
}

const props = defineProps<{
    clinics: Paginated<Clinic>;
    filters: { search?: string };
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.clinics.index().url,
    { search: filterValue(props.filters.search) },
);

const destroy = (clinic: Clinic): void => {
    confirmDelete(
        '¿Eliminar clínica?',
        `Se eliminará "${clinic.name}". Sus consultorios quedarán sin clínica.`,
        admin.clinics.destroy(clinic.id).url,
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Clínicas', href: admin.clinics.index() },
        ],
    },
});
</script>

<template>
    <Head title="Clínicas" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Clínicas odontológicas"
            description="Clínicas de la facultad y sus consultorios."
            section="Clínicas"
            :icon="Hospital"
            :action-icon="Plus"
            action-label="Nueva clínica"
            :action-href="admin.clinics.create().url"
        />

        <DataTable
            v-model:search="filters.search"
            :pagination="clinics"
            :is-empty="clinics.data.length === 0"
            :empty-colspan="5"
            search-placeholder="Buscar clínica..."
            :has-filters="hasFilters"
            @search="submit"
            @clear-filters="clear"
        >
            <template #active-filters>
                <FilterChip v-if="filters.search">Búsqueda: {{ filters.search }}</FilterChip>
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Clínica</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Departamento</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Espacios</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Estado</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Acciones</th>
                </tr>
            </template>

            <template #table-body>
                <tr
                    v-for="clinic in clinics.data"
                    :key="clinic.id"
                    class="transition-colors hover:bg-[#FAF3F4]"
                >
                    <td class="px-5 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]">
                                <Hospital class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <p class="font-semibold text-[#292D32]">{{ clinic.name }}</p>
                                <p class="mt-0.5 text-xs text-[#858B93]">
                                    {{ clinic.code }}<template v-if="clinic.location"> · {{ clinic.location }}</template>
                                </p>
                            </div>
                        </div>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ clinic.department?.name ?? '—' }}</td>
                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ clinic.spaces_count }}</td>
                    <td class="px-5 py-4 sm:px-6"><StatusBadge :value="clinic.is_active" /></td>

                    <TableActions
                        :view-url="admin.clinics.show(clinic.id).url"
                        :edit-url="admin.clinics.edit(clinic.id).url"
                        item-name="clínica"
                        @delete="destroy(clinic)"
                    />
                </tr>
            </template>

            <template #empty>
                <EmptyState
                    :icon="Hospital"
                    title="No se encontraron clínicas"
                    :description="hasFilters ? 'Prueba con otro término de búsqueda.' : 'Todavía no hay clínicas registradas.'"
                />
            </template>
        </DataTable>
    </div>
</template>
