<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { KeyRound, Plus, Users } from 'lucide-vue-next';

import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterChip from '@/components/admin/FilterChip.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import TableActions from '@/components/admin/TableActions.vue';
import { filterValue, useIndexFilters } from '@/composables/useIndexFilters';
import { confirmDelete, fullName } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Paginated, PersonName } from '@/types/admin';

interface Person extends PersonName {
    curp: string | null;
    user: { id: number; name: string; email: string } | null;
    student_profile: { id: number; registration_number: string; status: string } | null;
    teacher_profile: { id: number; employee_number: string | null; status: string } | null;
    staff_profile: { id: number; employee_number: string | null; position: string; status: string } | null;
}

const props = defineProps<{
    people: Paginated<Person>;
    filters: { search?: string; profile?: string };
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.people.index().url,
    {
        search: filterValue(props.filters.search),
        profile: filterValue(props.filters.profile),
    },
);

const profileOptions = [
    { value: 'student', label: 'Alumnos' },
    { value: 'teacher', label: 'Docentes' },
    { value: 'staff', label: 'Administrativos' },
];

const destroy = (person: Person): void => {
    confirmDelete(
        '¿Eliminar persona?',
        `Se eliminará a "${fullName(person)}" junto con sus perfiles.`,
        admin.people.destroy(person.id).url,
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Personas', href: admin.people.index() },
        ],
    },
});
</script>

<template>
    <Head title="Personas" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Personas"
            description="Alumnos, docentes y personal administrativo. Una persona puede tener varios perfiles."
            section="Comunidad"
            :icon="Users"
            :action-icon="Plus"
            action-label="Nueva persona"
            :action-href="admin.people.create().url"
        />

        <DataTable
            v-model:search="filters.search"
            :pagination="people"
            :is-empty="people.data.length === 0"
            :empty-colspan="5"
            search-placeholder="Buscar por nombre, CURP, RFC o teléfono..."
            :has-filters="hasFilters"
            @search="submit"
            @clear-filters="clear"
        >
            <template #filters>
                <FilterSelect v-model="filters.profile" placeholder="Todos los perfiles" :options="profileOptions" @change="submit" />
            </template>

            <template #active-filters>
                <FilterChip v-if="filters.search">Búsqueda: {{ filters.search }}</FilterChip>
                <FilterChip v-if="filters.profile">
                    {{ profileOptions.find((o) => o.value === filters.profile)?.label }}
                </FilterChip>
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Persona</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Contacto</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Perfiles</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Cuenta</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Acciones</th>
                </tr>
            </template>

            <template #table-body>
                <tr
                    v-for="person in people.data"
                    :key="person.id"
                    class="transition-colors hover:bg-[#FAF3F4]"
                >
                    <td class="px-5 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]">
                                <Users class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <p class="font-semibold text-[#292D32]">{{ fullName(person) }}</p>
                                <p class="mt-0.5 text-xs text-[#858B93]">{{ person.curp ?? 'Sin CURP' }}</p>
                            </div>
                        </div>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">
                        {{ person.phone ?? '—' }}
                        <p v-if="person.personal_email" class="break-all text-xs text-[#858B93]">{{ person.personal_email }}</p>
                    </td>

                    <td class="px-5 py-4 sm:px-6">
                        <div class="flex flex-wrap gap-1.5">
                            <span v-if="person.student_profile" class="inline-flex items-center rounded-full border border-[#E8C7CC] bg-[#F9ECEE] px-2.5 py-1 text-xs font-semibold text-[#941B2B]">
                                Alumno · {{ person.student_profile.registration_number }}
                            </span>
                            <span v-if="person.teacher_profile" class="inline-flex items-center rounded-full border border-[#BFE3CC] bg-[#E2F3E8] px-2.5 py-1 text-xs font-semibold text-[#187744]">
                                Docente
                            </span>
                            <span v-if="person.staff_profile" class="inline-flex items-center rounded-full border border-[#C7D7EE] bg-[#EAF1FB] px-2.5 py-1 text-xs font-semibold text-[#1F4F8F]">
                                {{ person.staff_profile.position }}
                            </span>
                            <span
                                v-if="!person.student_profile && !person.teacher_profile && !person.staff_profile"
                                class="text-xs italic text-[#858B93]"
                            >
                                Sin perfil
                            </span>
                        </div>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">
                        <span v-if="person.user" class="inline-flex items-center gap-1.5 break-all text-xs">
                            <KeyRound class="h-3.5 w-3.5 shrink-0 text-[#941B2B]" /> {{ person.user.email }}
                        </span>
                        <span v-else class="text-xs italic text-[#858B93]">Sin cuenta</span>
                    </td>

                    <TableActions
                        :view-url="admin.people.show(person.id).url"
                        :edit-url="admin.people.edit(person.id).url"
                        item-name="persona"
                        @delete="destroy(person)"
                    />
                </tr>
            </template>

            <template #empty>
                <EmptyState
                    :icon="Users"
                    title="No se encontraron personas"
                    :description="hasFilters ? 'Prueba con otros filtros.' : 'Todavía no hay personas registradas.'"
                />
            </template>
        </DataTable>
    </div>
</template>
