<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Plus, UserRoundCheck } from 'lucide-vue-next';

import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterChip from '@/components/admin/FilterChip.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import TableActions from '@/components/admin/TableActions.vue';
import { filterValue, useIndexFilters } from '@/composables/useIndexFilters';
import { fullName, label, recordOptions } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option, Paginated, PersonName } from '@/types/admin';

interface Teacher {
    id: number;
    employee_number: string | null;
    professional_license: string | null;
    professional_title: string | null;
    status: string;
    people: PersonName | null;
    specialties: Option[];
    academic_programs: Option[];
}

const props = defineProps<{
    teachers: Paginated<Teacher>;
    filters: { search?: string; specialty_id?: string; academic_program_id?: string; status?: string };
    specialties: Option[];
    programs: Option[];
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.teachers.index().url,
    {
        search: filterValue(props.filters.search),
        specialty_id: filterValue(props.filters.specialty_id),
        academic_program_id: filterValue(props.filters.academic_program_id),
        status: filterValue(props.filters.status),
    },
);

const nameOf = (list: Option[], id: string) => list.find((item) => String(item.id) === id)?.name;

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Docentes', href: admin.teachers.index() },
        ],
    },
});
</script>

<template>
    <Head title="Docentes" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Docentes y especialistas"
            description="Formación, especialidades, programas y disponibilidad de los docentes."
            section="Docentes"
            :icon="UserRoundCheck"
            :action-icon="Plus"
            action-label="Registrar docente"
            :action-href="admin.people.create({ query: { profile: 'teacher' } }).url"
        />

        <DataTable
            v-model:search="filters.search"
            :pagination="teachers"
            :is-empty="teachers.data.length === 0"
            :empty-colspan="5"
            search-placeholder="Buscar por nombre, número o cédula..."
            :has-filters="hasFilters"
            @search="submit"
            @clear-filters="clear"
        >
            <template #filters>
                <FilterSelect v-model="filters.specialty_id" placeholder="Todas las especialidades" :options="recordOptions(specialties)" @change="submit" />
                <FilterSelect v-model="filters.academic_program_id" placeholder="Todos los programas" :options="recordOptions(programs, true)" @change="submit" />
                <FilterSelect
                    v-model="filters.status"
                    placeholder="Cualquier estado"
                    :options="[{ value: 'active', label: 'Activos' }, { value: 'inactive', label: 'Inactivos' }]"
                    @change="submit"
                />
            </template>

            <template #active-filters>
                <FilterChip v-if="filters.search">Búsqueda: {{ filters.search }}</FilterChip>
                <FilterChip v-if="filters.specialty_id">Especialidad: {{ nameOf(specialties, filters.specialty_id) }}</FilterChip>
                <FilterChip v-if="filters.academic_program_id">Programa: {{ nameOf(programs, filters.academic_program_id) }}</FilterChip>
                <FilterChip v-if="filters.status">Estado: {{ label(filters.status) }}</FilterChip>
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Docente</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Especialidades</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Programas</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Estado</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Acciones</th>
                </tr>
            </template>

            <template #table-body>
                <tr
                    v-for="teacher in teachers.data"
                    :key="teacher.id"
                    class="transition-colors hover:bg-[#FAF3F4]"
                >
                    <td class="px-5 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]">
                                <UserRoundCheck class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <p class="font-semibold text-[#292D32]">{{ fullName(teacher.people) }}</p>
                                <p class="mt-0.5 text-xs text-[#858B93]">
                                    {{ teacher.professional_title ?? 'Sin título registrado' }}
                                    <template v-if="teacher.professional_license"> · Cédula {{ teacher.professional_license }}</template>
                                </p>
                            </div>
                        </div>
                    </td>

                    <td class="px-5 py-4 sm:px-6">
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="specialty in teacher.specialties"
                                :key="specialty.id"
                                class="inline-flex items-center rounded-full border border-[#E8C7CC] bg-[#F9ECEE] px-2.5 py-1 text-xs font-semibold text-[#941B2B]"
                            >
                                {{ specialty.name }}
                            </span>
                            <span v-if="!teacher.specialties.length" class="text-xs italic text-[#858B93]">—</span>
                        </div>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">
                        {{ teacher.academic_programs.map((program) => program.code).join(', ') || '—' }}
                    </td>

                    <td class="px-5 py-4 sm:px-6"><StatusBadge :value="teacher.status" /></td>

                    <TableActions
                        :view-url="admin.teachers.show(teacher.id).url"
                        :edit-url="teacher.people ? admin.people.edit(teacher.people.id).url : undefined"
                        :show-delete="false"
                        item-name="docente"
                    />
                </tr>
            </template>

            <template #empty>
                <EmptyState
                    :icon="UserRoundCheck"
                    title="No se encontraron docentes"
                    :description="hasFilters ? 'Prueba con otros filtros.' : 'Registra docentes desde Personas con perfil de docente.'"
                />
            </template>
        </DataTable>
    </div>
</template>
