<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { CalendarClock, GraduationCap, Hash, Search, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import AdminCard from '@/components/admin/AdminCard.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import { fullName } from '@/lib/admin';
import type { StudentOption } from '@/types/admin';
import type { AcademicGroupFormData } from '@/types/forms';

const props = defineProps<{
    form: InertiaForm<AcademicGroupFormData>;
    periods: { id: number; name: string; is_current: boolean }[];
    cohorts: { id: number; name: string; academic_program: { id: number; code: string } | null }[];
    students: StudentOption[];
}>();

const search = ref('');

const periodOptions = computed(() =>
    props.periods.map((period) => ({
        value: period.id,
        label: period.is_current ? `${period.name} (vigente)` : period.name,
    })),
);

const cohortOptions = computed(() =>
    props.cohorts.map((cohort) => ({
        value: cohort.id,
        label: `${cohort.academic_program?.code ?? ''} · ${cohort.name}`,
    })),
);

const filteredStudents = computed(() => {
    const term = search.value.trim().toLowerCase();

    if (!term) {
        return props.students;
    }

    return props.students.filter(
        (student) =>
            fullName(student.people).toLowerCase().includes(term)
            || student.registration_number.toLowerCase().includes(term),
    );
});

const toggleStudent = (id: number): void => {
    const index = props.form.student_ids.indexOf(id);

    if (index === -1) {
        props.form.student_ids.push(id);
    } else {
        props.form.student_ids.splice(index, 1);
    }
};

const toggleAll = (): void => {
    const ids = filteredStudents.value.map((student) => student.id);
    const allSelected = ids.every((id) => props.form.student_ids.includes(id));

    props.form.student_ids = allSelected
        ? props.form.student_ids.filter((id) => !ids.includes(id))
        : [...new Set([...props.form.student_ids, ...ids])];
};
</script>

<template>
    <AdminCard
        title="Información del grupo"
        description="Un grupo reúne a alumnos de una generación que cursan el mismo semestre en un periodo."
        :icon="CalendarClock"
    >
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <FormSelect
                id="academic_period_id"
                v-model="form.academic_period_id"
                label="Periodo académico"
                :options="periodOptions"
                :error="form.errors.academic_period_id"
                required
            />

            <FormSelect
                id="cohort_id"
                v-model="form.cohort_id"
                label="Generación"
                :options="cohortOptions"
                :error="form.errors.cohort_id"
                required
            />

            <FormInput
                id="code"
                v-model="form.code"
                label="Clave del grupo"
                placeholder="Ej. ORTO-1A"
                :icon="Hash"
                :error="form.errors.code"
                required
            />

            <div class="grid grid-cols-2 gap-5">
                <FormInput
                    id="period_number"
                    v-model="form.period_number"
                    type="number"
                    min="1"
                    label="Semestre"
                    :icon="GraduationCap"
                    :error="form.errors.period_number"
                    required
                />

                <FormInput
                    id="capacity"
                    v-model="form.capacity"
                    type="number"
                    min="1"
                    label="Cupo"
                    :icon="Users"
                    :error="form.errors.capacity"
                />
            </div>
        </div>
    </AdminCard>

    <AdminCard
        title="Alumnos del grupo"
        description="Alumnos con inscripción activa. Selecciona a quienes integran el grupo."
        :icon="Users"
    >
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:max-w-sm">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#858B93]" />
                <input
                    v-model="search"
                    type="search"
                    placeholder="Buscar alumno o matrícula..."
                    class="form-input pl-10"
                />
            </div>

            <div class="flex items-center gap-3">
                <span
                    class="inline-flex items-center rounded-full bg-[#F9ECEE] px-3 py-1.5 text-sm font-semibold text-[#941B2B]"
                    :class="{ 'bg-[#FDF6E7] text-[#8A5A00]': form.capacity && form.student_ids.length > form.capacity }"
                >
                    {{ form.student_ids.length }}{{ form.capacity ? ` / ${form.capacity}` : '' }} seleccionados
                </span>

                <button
                    type="button"
                    class="text-sm font-semibold text-[#941B2B] hover:underline"
                    @click="toggleAll"
                >
                    Seleccionar / quitar visibles
                </button>
            </div>
        </div>

        <div v-if="filteredStudents.length" class="grid max-h-[420px] gap-2 overflow-y-auto sm:grid-cols-2 lg:grid-cols-3">
            <label
                v-for="student in filteredStudents"
                :key="student.id"
                class="flex cursor-pointer items-center gap-3 rounded-xl border border-[#E3E5E8] bg-white px-4 py-3 transition hover:border-[#B8BEC6] hover:bg-[#F4F5F6]"
                :class="{ 'border-[#E8C7CC] bg-[#FDF8F8]': form.student_ids.includes(student.id) }"
            >
                <input
                    type="checkbox"
                    class="form-checkbox shrink-0"
                    :checked="form.student_ids.includes(student.id)"
                    @change="toggleStudent(student.id)"
                />

                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-[#292D32]">{{ fullName(student.people) }}</span>
                    <span class="block text-xs text-[#858B93]">{{ student.registration_number }}</span>
                </span>
            </label>
        </div>

        <p v-else class="text-sm italic text-[#858B93]">
            No hay alumnos con inscripción activa{{ search ? ' que coincidan con la búsqueda' : '' }}.
        </p>

        <p v-if="form.errors.student_ids" class="form-error">{{ form.errors.student_ids }}</p>
    </AdminCard>
</template>
