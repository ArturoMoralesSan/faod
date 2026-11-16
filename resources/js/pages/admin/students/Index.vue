<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { GraduationCap, Plus } from 'lucide-vue-next';
import { computed } from 'vue';

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
import type { Option, Paginated, StudentOption } from '@/types/admin';

interface Enrollment {
    id: number;
    status: string;
    student: StudentOption;
    academic_program: Option & { specialty: Option | null };
    cohort: Option | null;
    university_registrations: { id: number; status: string }[];
}

const props = defineProps<{
    enrollments: Paginated<Enrollment>;
    filters: {
        search?: string;
        academic_program_id?: string;
        specialty_id?: string;
        cohort_id?: string;
        status?: string;
        university_status?: string;
        academic_period_id?: number | string | null;
    };
    statuses: string[];
    universityStatuses: string[];
    programs: (Option & { cohorts: Option[] })[];
    specialties: Option[];
    periods: Option[];
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.students.index().url,
    {
        search: filterValue(props.filters.search),
        academic_program_id: filterValue(props.filters.academic_program_id),
        specialty_id: filterValue(props.filters.specialty_id),
        cohort_id: filterValue(props.filters.cohort_id),
        status: filterValue(props.filters.status),
        university_status: filterValue(props.filters.university_status),
        academic_period_id: filterValue(props.filters.academic_period_id),
    },
);

const cohortOptions = computed(() =>
    recordOptions(
        props.programs.find((program) => String(program.id) === filters.academic_program_id)?.cohorts ?? [],
    ),
);

const nameOf = (list: Option[], id: string) => list.find((item) => String(item.id) === id)?.name;

const universityStatus = (enrollment: Enrollment): string =>
    enrollment.university_registrations[0]?.status ?? 'pendiente';

const destroy = (enrollment: Enrollment): void => {
    confirmDelete(
        '¿Eliminar inscripción?',
        `Se eliminará la inscripción de ${fullName(enrollment.student.people)} en ${enrollment.academic_program.name}.`,
        admin.students.destroy(enrollment.id).url,
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Alumnos', href: admin.students.index() },
        ],
    },
});
</script>

<template>
    <Head title="Alumnos" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Control interno de alumnos"
            description="Inscripción interna, situación ante la universidad, bajas y egresos."
            section="Alumnos"
            :icon="GraduationCap"
            :action-icon="Plus"
            action-label="Inscribir alumno"
            :action-href="admin.students.create().url"
        />

        <DataTable
            v-model:search="filters.search"
            :pagination="enrollments"
            :is-empty="enrollments.data.length === 0"
            :empty-colspan="6"
            search-placeholder="Buscar por nombre o matrícula..."
            :has-filters="hasFilters"
            @search="submit"
            @clear-filters="clear"
        >
            <template #filters>
                <FilterSelect v-model="filters.academic_program_id" placeholder="Todos los programas" :options="recordOptions(programs, true)" @change="filters.cohort_id = ''; submit()" />
                <FilterSelect v-if="filters.academic_program_id" v-model="filters.cohort_id" placeholder="Todas las generaciones" :options="cohortOptions" @change="submit" />
                <FilterSelect v-model="filters.specialty_id" placeholder="Todas las especialidades" :options="recordOptions(specialties)" @change="submit" />
                <FilterSelect v-model="filters.status" placeholder="Cualquier situación" :options="enumOptions(statuses)" @change="submit" />
                <FilterSelect v-model="filters.academic_period_id" placeholder="Periodo vigente" :options="recordOptions(periods)" @change="submit" />
                <FilterSelect v-model="filters.university_status" placeholder="Inscripción UJED" :options="enumOptions(universityStatuses)" @change="submit" />
            </template>

            <template #active-filters>
                <FilterChip v-if="filters.search">Búsqueda: {{ filters.search }}</FilterChip>
                <FilterChip v-if="filters.academic_program_id">Programa: {{ nameOf(programs, filters.academic_program_id) }}</FilterChip>
                <FilterChip v-if="filters.cohort_id">Generación: {{ cohortOptions.find((o) => String(o.value) === filters.cohort_id)?.label }}</FilterChip>
                <FilterChip v-if="filters.specialty_id">Especialidad: {{ nameOf(specialties, filters.specialty_id) }}</FilterChip>
                <FilterChip v-if="filters.status">Situación: {{ label(filters.status) }}</FilterChip>
                <FilterChip v-if="filters.academic_period_id">Periodo: {{ nameOf(periods, filters.academic_period_id) }}</FilterChip>
                <FilterChip v-if="filters.university_status">Universidad: {{ label(filters.university_status) }}</FilterChip>
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Alumno</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Programa</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Generación</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Situación interna</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Inscripción universidad</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Acciones</th>
                </tr>
            </template>

            <template #table-body>
                <tr
                    v-for="enrollment in enrollments.data"
                    :key="enrollment.id"
                    class="transition-colors hover:bg-[#FAF3F4]"
                >
                    <td class="px-5 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]">
                                <GraduationCap class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <p class="font-semibold text-[#292D32]">{{ fullName(enrollment.student.people) }}</p>
                                <p class="mt-0.5 text-xs text-[#858B93]">Matrícula: {{ enrollment.student.registration_number }}</p>
                            </div>
                        </div>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">
                        {{ enrollment.academic_program.name }}
                        <p v-if="enrollment.academic_program.specialty" class="text-xs text-[#858B93]">
                            {{ enrollment.academic_program.specialty.name }}
                        </p>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ enrollment.cohort?.name ?? '—' }}</td>
                    <td class="px-5 py-4 sm:px-6"><StatusBadge :value="enrollment.status" /></td>
                    <td class="px-5 py-4 sm:px-6"><StatusBadge :value="universityStatus(enrollment)" /></td>

                    <TableActions
                        :view-url="admin.students.show(enrollment.id).url"
                        :edit-url="admin.students.edit(enrollment.id).url"
                        item-name="inscripción"
                        @delete="destroy(enrollment)"
                    />
                </tr>
            </template>

            <template #empty>
                <EmptyState
                    :icon="GraduationCap"
                    title="No se encontraron alumnos"
                    :description="hasFilters ? 'Prueba con otros filtros.' : 'Todavía no hay alumnos inscritos.'"
                />
            </template>
        </DataTable>
    </div>
</template>
