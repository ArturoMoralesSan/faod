<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Contact, Plus, TriangleAlert } from 'lucide-vue-next';

import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterChip from '@/components/admin/FilterChip.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import TableActions from '@/components/admin/TableActions.vue';
import { filterValue, useIndexFilters } from '@/composables/useIndexFilters';
import { age, confirmDelete, enumOptions, fullName, label, recordOptions } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option, Paginated, PersonName, SelectOption } from '@/types/admin';

interface Patient {
    id: number;
    record_number: string;
    status: string;
    allergies_count: number;
    people: PersonName & { birth_date: string | null; curp: string | null };
    clinic: Option | null;
}

const props = defineProps<{
    patients: Paginated<Patient>;
    filters: { search?: string; status?: string; clinic_id?: string; student_profile_id?: string; specialty_id?: string };
    statuses: string[];
    clinics: Option[];
    students: SelectOption[];
    specialties: Option[];
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.patients.index().url,
    {
        search: filterValue(props.filters.search),
        status: filterValue(props.filters.status),
        clinic_id: filterValue(props.filters.clinic_id),
        student_profile_id: filterValue(props.filters.student_profile_id),
        specialty_id: filterValue(props.filters.specialty_id),
    },
);

const clinicName = (id: string) => props.clinics.find((c) => String(c.id) === id)?.name;
const studentName = (id: string) => props.students.find((s) => String(s.value) === id)?.label;
const specialtyName = (id: string) => props.specialties.find((s) => String(s.id) === id)?.name;

const destroy = (patient: Patient): void => {
    confirmDelete(
        '¿Eliminar expediente?',
        `Se eliminará el expediente ${patient.record_number} con sus consultas y notas. La persona se conserva.`,
        admin.patients.destroy(patient.id).url,
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Pacientes', href: admin.patients.index() },
        ],
    },
});
</script>

<template>
    <Head title="Pacientes" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Pacientes"
            description="Expedientes clínicos de los pacientes atendidos en la facultad."
            section="Atención clínica"
            :icon="Contact"
            :action-icon="Plus"
            action-label="Nuevo paciente"
            :action-href="admin.patients.create().url"
        />

        <DataTable
            v-model:search="filters.search"
            :pagination="patients"
            :is-empty="patients.data.length === 0"
            :empty-colspan="5"
            search-placeholder="Buscar por nombre, expediente, CURP o teléfono..."
            :has-filters="hasFilters"
            @search="submit"
            @clear-filters="clear"
        >
            <template #filters>
                <FilterSelect v-model="filters.status" placeholder="Todos los estados" :options="enumOptions(statuses)" @change="submit" />
                <FilterSelect v-model="filters.clinic_id" placeholder="Todas las clínicas" :options="recordOptions(clinics)" @change="submit" />
                <FilterSelect v-model="filters.student_profile_id" placeholder="Atendidos por cualquier alumno" :options="students" @change="submit" />
                <FilterSelect v-model="filters.specialty_id" placeholder="Todas las especialidades" :options="recordOptions(specialties)" @change="submit" />
            </template>

            <template #active-filters>
                <FilterChip v-if="filters.search">Búsqueda: {{ filters.search }}</FilterChip>
                <FilterChip v-if="filters.status">Estado: {{ label(filters.status) }}</FilterChip>
                <FilterChip v-if="filters.clinic_id">Clínica: {{ clinicName(filters.clinic_id) }}</FilterChip>
                <FilterChip v-if="filters.student_profile_id">Alumno: {{ studentName(filters.student_profile_id) }}</FilterChip>
                <FilterChip v-if="filters.specialty_id">Especialidad: {{ specialtyName(filters.specialty_id) }}</FilterChip>
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Paciente</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Contacto</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Clínica</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Estado</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Acciones</th>
                </tr>
            </template>

            <template #table-body>
                <tr v-for="patient in patients.data" :key="patient.id" class="transition-colors hover:bg-[#FAF3F4]">
                    <td class="px-5 py-4 sm:px-6">
                        <p class="flex items-center gap-1.5 font-semibold text-[#292D32]">
                            {{ fullName(patient.people) }}
                            <TriangleAlert v-if="patient.allergies_count" class="h-4 w-4 text-[#941B2B]" title="Tiene alergias registradas" />
                        </p>
                        <p class="mt-0.5 text-xs text-[#858B93]">
                            {{ patient.record_number }}
                            <template v-if="age(patient.people.birth_date) !== null"> · {{ age(patient.people.birth_date) }} años</template>
                        </p>
                    </td>
                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ patient.people.phone ?? '—' }}</td>
                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ patient.clinic?.name ?? '—' }}</td>
                    <td class="px-5 py-4 sm:px-6"><StatusBadge :value="patient.status" /></td>

                    <TableActions
                        :view-url="admin.patients.show(patient.id).url"
                        :edit-url="admin.patients.edit(patient.id).url"
                        item-name="expediente"
                        @delete="destroy(patient)"
                    />
                </tr>
            </template>

            <template #empty>
                <EmptyState
                    :icon="Contact"
                    title="No se encontraron pacientes"
                    :description="hasFilters ? 'Prueba con otra búsqueda o filtros.' : 'Todavía no hay expedientes registrados.'"
                />
            </template>
        </DataTable>
    </div>
</template>
