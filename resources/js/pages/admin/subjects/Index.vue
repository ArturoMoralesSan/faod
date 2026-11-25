<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { BookOpen, Plus } from 'lucide-vue-next';

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

interface Subject {
    id: number;
    code: string;
    name: string;
    credits: string | null;
    theory_hours: number;
    practice_hours: number;
    is_active: boolean;
    study_plans_count: number;
}

const props = defineProps<{
    subjects: Paginated<Subject>;
    filters: { search?: string };
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.subjects.index().url,
    { search: filterValue(props.filters.search) },
);

const destroy = (subject: Subject): void => {
    confirmDelete(
        '¿Eliminar asignatura?',
        `Se eliminará "${subject.name}".`,
        admin.subjects.destroy(subject.id).url,
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Asignaturas', href: admin.subjects.index() },
        ],
    },
});
</script>

<template>
    <Head title="Asignaturas" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Asignaturas"
            description="Catálogo de asignaturas y materias."
            section="Programas académicos"
            :icon="BookOpen"
            :action-icon="Plus"
            action-label="Nueva asignatura"
            :action-href="admin.subjects.create().url"
        />

        <DataTable
            v-model:search="filters.search"
            :pagination="subjects"
            :is-empty="subjects.data.length === 0"
            :empty-colspan="6"
            search-placeholder="Buscar por nombre o clave..."
            :has-filters="hasFilters"
            @search="submit"
            @clear-filters="clear"
        >
            <template #active-filters>
                <FilterChip v-if="filters.search">Búsqueda: {{ filters.search }}</FilterChip>
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Asignatura</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Créditos</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Horas (T/P)</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Planes</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Estado</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Acciones</th>
                </tr>
            </template>

            <template #table-body>
                <tr
                    v-for="subject in subjects.data"
                    :key="subject.id"
                    class="transition-colors hover:bg-[#F4F5F6]"
                >
                    <td class="px-5 py-4 sm:px-6">
                        <p class="font-semibold text-[#292D32]">{{ subject.name }}</p>
                        <p class="mt-0.5 text-xs text-[#858B93]">{{ subject.code }}</p>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ subject.credits ?? '—' }}</td>
                    <td class="px-5 py-4 text-[#525960] sm:px-6">
                        {{ subject.theory_hours }} / {{ subject.practice_hours }}
                    </td>
                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ subject.study_plans_count }}</td>
                    <td class="px-5 py-4 sm:px-6"><StatusBadge :value="subject.is_active" /></td>

                    <TableActions
                        :edit-url="admin.subjects.edit(subject.id).url"
                        :show-view="false"
                        item-name="asignatura"
                        @delete="destroy(subject)"
                    />
                </tr>
            </template>

            <template #empty>
                <EmptyState
                    :icon="BookOpen"
                    title="No se encontraron asignaturas"
                    :description="hasFilters ? 'Prueba con otro término de búsqueda.' : 'Todavía no hay asignaturas registradas.'"
                />
            </template>
        </DataTable>
    </div>
</template>
