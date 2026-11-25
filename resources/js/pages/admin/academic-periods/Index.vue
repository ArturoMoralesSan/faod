<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CalendarDays, Plus } from 'lucide-vue-next';

import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import TableActions from '@/components/admin/TableActions.vue';
import { confirmDelete, formatDate } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Paginated } from '@/types/admin';

interface Period {
    id: number;
    code: string;
    name: string;
    starts_at: string;
    ends_at: string;
    is_current: boolean;
    groups_count: number;
}

defineProps<{
    periods: Paginated<Period>;
}>();

const destroy = (period: Period): void => {
    confirmDelete(
        '¿Eliminar periodo?',
        `Se eliminará "${period.name}" junto con sus grupos y horarios.`,
        admin.academicPeriods.destroy(period.id).url,
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Periodos académicos', href: admin.academicPeriods.index() },
        ],
    },
});
</script>

<template>
    <Head title="Periodos académicos" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Periodos académicos"
            description="Semestres o periodos escolares de la facultad."
            section="Programas académicos"
            :icon="CalendarDays"
            :action-icon="Plus"
            action-label="Nuevo periodo"
            :action-href="admin.academicPeriods.create().url"
        />

        <DataTable
            :pagination="periods"
            :is-empty="periods.data.length === 0"
            :empty-colspan="5"
            :show-search="false"
        >
            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Periodo</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Vigencia</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Grupos</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Estado</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Acciones</th>
                </tr>
            </template>

            <template #table-body>
                <tr
                    v-for="period in periods.data"
                    :key="period.id"
                    class="transition-colors hover:bg-[#F4F5F6]"
                >
                    <td class="px-5 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]">
                                <CalendarDays class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <p class="font-semibold text-[#292D32]">{{ period.name }}</p>
                                <p class="mt-0.5 text-xs text-[#858B93]">{{ period.code }}</p>
                            </div>
                        </div>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">
                        {{ formatDate(period.starts_at) }} – {{ formatDate(period.ends_at) }}
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ period.groups_count }}</td>

                    <td class="px-5 py-4 sm:px-6">
                        <StatusBadge
                            :value="period.is_current ? 'activo' : null"
                            :label="period.is_current ? 'Vigente' : 'No vigente'"
                        />
                    </td>

                    <TableActions
                        :edit-url="admin.academicPeriods.edit(period.id).url"
                        :show-view="false"
                        item-name="periodo"
                        @delete="destroy(period)"
                    />
                </tr>
            </template>

            <template #empty>
                <EmptyState
                    :icon="CalendarDays"
                    title="Sin periodos registrados"
                    description="Registra el primer periodo académico."
                />
            </template>
        </DataTable>
    </div>
</template>
