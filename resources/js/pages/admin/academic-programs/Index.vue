<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { GraduationCap, Plus } from 'lucide-vue-next';

import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterChip from '@/components/admin/FilterChip.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import TableActions from '@/components/admin/TableActions.vue';
import { filterValue, useIndexFilters } from '@/composables/useIndexFilters';
import { confirmDelete, enumOptions, fullName, label, recordOptions } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option, Paginated, TeacherOption } from '@/types/admin';

interface Program {
    id: number;
    code: string;
    name: string;
    type: string;
    is_active: boolean;
    duration_value: number | null;
    duration_unit: string | null;
    specialty: Option | null;
    department: Option | null;
    coordinators: TeacherOption[];
    cohorts_count: number;
    active_students_count: number;
}

const props = defineProps<{
    programs: Paginated<Program>;
    filters: { search?: string; type?: string; specialty_id?: string; is_active?: string };
    types: string[];
    specialties: Option[];
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.academicPrograms.index().url,
    {
        search: filterValue(props.filters.search),
        type: filterValue(props.filters.type),
        specialty_id: filterValue(props.filters.specialty_id),
        is_active: filterValue(props.filters.is_active),
    },
);

const specialtyName = (id: string) => props.specialties.find((s) => String(s.id) === id)?.name;

const destroy = (program: Program): void => {
    confirmDelete(
        '¿Eliminar programa?',
        `Se eliminará "${program.name}". Esta acción no se puede deshacer.`,
        admin.academicPrograms.destroy(program.id).url,
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Programas académicos', href: admin.academicPrograms.index() },
        ],
    },
});
</script>

<template>
    <Head title="Programas académicos" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Programas académicos"
            description="Especialidades, maestrías, doctorados y diplomados."
            section="Programas académicos"
            :icon="GraduationCap"
            :action-icon="Plus"
            action-label="Nuevo programa"
            :action-href="admin.academicPrograms.create().url"
            action-permission="academic-programs.create"
        />

        <DataTable
            v-model:search="filters.search"
            :pagination="programs"
            :is-empty="programs.data.length === 0"
            :empty-colspan="6"
            search-placeholder="Buscar por nombre o clave..."
            :has-filters="hasFilters"
            @search="submit"
            @clear-filters="clear"
        >
            <template #filters>
                <FilterSelect v-model="filters.type" placeholder="Todos los tipos" :options="enumOptions(types)" @change="submit" />
                <FilterSelect v-model="filters.specialty_id" placeholder="Todas las especialidades" :options="recordOptions(specialties)" @change="submit" />
                <FilterSelect
                    v-model="filters.is_active"
                    placeholder="Cualquier estado"
                    :options="[{ value: '1', label: 'Activos' }, { value: '0', label: 'Inactivos' }]"
                    @change="submit"
                />
            </template>

            <template #active-filters>
                <FilterChip v-if="filters.search">Búsqueda: {{ filters.search }}</FilterChip>
                <FilterChip v-if="filters.type">Tipo: {{ label(filters.type) }}</FilterChip>
                <FilterChip v-if="filters.specialty_id">Especialidad: {{ specialtyName(filters.specialty_id) }}</FilterChip>
                <FilterChip v-if="filters.is_active">{{ filters.is_active === '1' ? 'Activos' : 'Inactivos' }}</FilterChip>
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Programa</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Tipo</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Coordinación</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Alumnos activos</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Estado</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Acciones</th>
                </tr>
            </template>

            <template #table-body>
                <tr
                    v-for="program in programs.data"
                    :key="program.id"
                    class="transition-colors hover:bg-[#F4F5F6]"
                >
                    <td class="px-5 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]">
                                <GraduationCap class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <p class="font-semibold text-[#292D32]">{{ program.name }}</p>
                                <p class="mt-0.5 text-xs text-[#858B93]">
                                    {{ program.code }}
                                    <template v-if="program.specialty"> · {{ program.specialty.name }}</template>
                                </p>
                            </div>
                        </div>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">
                        {{ label(program.type) }}
                        <p v-if="program.duration_value" class="text-xs text-[#858B93]">
                            {{ program.duration_value }} {{ label(program.duration_unit).toLowerCase() }}
                        </p>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">
                        <template v-if="program.coordinators.length">
                            {{ program.coordinators.map((c) => fullName(c.people)).join(', ') }}
                        </template>
                        <span v-else class="text-xs italic text-[#858B93]">Sin coordinador</span>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">
                        {{ program.active_students_count }}
                        <p class="text-xs text-[#858B93]">{{ program.cohorts_count }} generaciones</p>
                    </td>

                    <td class="px-5 py-4 sm:px-6"><StatusBadge :value="program.is_active" /></td>

                    <TableActions
                        permission="academic-programs"
                        :view-url="admin.academicPrograms.show(program.id).url"
                        :edit-url="admin.academicPrograms.edit(program.id).url"
                        item-name="programa"
                        @delete="destroy(program)"
                    />
                </tr>
            </template>

            <template #empty>
                <EmptyState
                    :icon="GraduationCap"
                    title="No se encontraron programas"
                    :description="hasFilters ? 'Prueba con otros filtros.' : 'Todavía no hay programas registrados.'"
                />
            </template>
        </DataTable>
    </div>
</template>
