<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { BookOpen, CalendarClock, Clock, GraduationCap, Pencil, Plus, Trash2, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import AdminButton from '@/components/admin/AdminButton.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import WeeklySchedule from '@/components/admin/WeeklySchedule.vue';
import { confirmDelete, formatTime, fullName, label } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Days, Option, PersonName, ScheduleBlock, StudentOption, TeacherOption } from '@/types/admin';

interface Schedule {
    id: number;
    class_section_id: number;
    space_id: number | null;
    day_of_week: number;
    start_time: string;
    end_time: string;
    space: { id: number; code: string; name: string } | null;
}

interface Section {
    id: number;
    subject_id: number;
    teacher_profile_id: number | null;
    subject: { id: number; code: string; name: string };
    teacher: { id: number; people: PersonName | null } | null;
    schedules: Schedule[];
}

const props = defineProps<{
    group: {
        id: number;
        code: string;
        period_number: number;
        capacity: number | null;
        cohort: { id: number; name: string; academic_program: { id: number; name: string; code: string } };
        academic_period: Option;
        students: StudentOption[];
        class_sections: Section[];
    };
    days: Days;
    planSubjects: { id: number; code: string; name: string }[];
    teachers: TeacherOption[];
    spaces: { id: number; code: string; name: string; type: string }[];
}>();

const teacherOptions = computed(() =>
    props.teachers.map((teacher) => ({ value: teacher.id, label: fullName(teacher.people) })),
);

const spaceOptions = computed(() =>
    props.spaces.map((space) => ({ value: space.id, label: `${space.code} · ${space.name} (${label(space.type)})` })),
);

const dayOptions = computed(() =>
    Object.entries(props.days).map(([value, name]) => ({ value: Number(value), label: name })),
);

const sectionOptions = computed(() =>
    props.group.class_sections.map((section) => ({ value: section.id, label: section.subject.name })),
);

// Materias del plan que aún no están asignadas al grupo.
const availableSubjects = computed(() =>
    props.planSubjects.filter(
        (subject) => !props.group.class_sections.some((section) => section.subject_id === subject.id),
    ),
);

const blocks = computed<ScheduleBlock[]>(() =>
    props.group.class_sections.flatMap((section) =>
        section.schedules.map((schedule) => ({
            id: schedule.id,
            day_of_week: schedule.day_of_week,
            start_time: schedule.start_time,
            end_time: schedule.end_time,
            title: section.subject.name,
            subtitle: section.teacher ? fullName(section.teacher.people) : 'Sin docente',
            meta: schedule.space ? `${schedule.space.code} · ${schedule.space.name}` : 'Sin aula',
            colorKey: section.subject_id,
        })),
    ),
);

const weeklyHours = (section: Section): number =>
    section.schedules.reduce((total, schedule) => {
        const [sh, sm] = schedule.start_time.split(':').map(Number);
        const [eh, em] = schedule.end_time.split(':').map(Number);

        return total + (eh * 60 + em - (sh * 60 + sm)) / 60;
    }, 0);

/* ------------------------------ Materias ------------------------------ */

const sectionDialog = ref(false);
const editingSection = ref<Section | null>(null);
const sectionForm = useForm({
    subject_id: null as number | null,
    teacher_profile_id: null as number | null,
});

const openSection = (section: Section | null = null): void => {
    editingSection.value = section;
    sectionForm.clearErrors();
    sectionForm.subject_id = section?.subject_id ?? null;
    sectionForm.teacher_profile_id = section?.teacher_profile_id ?? null;
    sectionDialog.value = true;
};

const submitSection = (): void => {
    const options = { preserveScroll: true, onSuccess: () => (sectionDialog.value = false) };

    if (editingSection.value) {
        sectionForm
            .transform(({ teacher_profile_id }) => ({ teacher_profile_id }))
            .put(admin.classSections.update(editingSection.value.id).url, options);
    } else {
        sectionForm
            .transform((data) => data)
            .post(admin.groups.sections.store(props.group.id).url, options);
    }
};

const destroySection = (section: Section): void => {
    confirmDelete(
        '¿Quitar materia?',
        `Se quitará "${section.subject.name}" del grupo junto con sus horarios.`,
        admin.classSections.destroy(section.id).url,
        { preserveState: true },
    );
};

/* ------------------------------ Horarios ------------------------------ */

const scheduleDialog = ref(false);
const editingSchedule = ref<Schedule | null>(null);
const scheduleForm = useForm({
    class_section_id: null as number | null,
    space_id: null as number | null,
    day_of_week: 1 as number | null,
    start_time: '08:00',
    end_time: '10:00',
});

const openSchedule = (schedule: Schedule | null = null, sectionId: number | null = null): void => {
    editingSchedule.value = schedule;
    scheduleForm.clearErrors();
    scheduleForm.class_section_id = schedule?.class_section_id ?? sectionId;
    scheduleForm.space_id = schedule?.space_id ?? null;
    scheduleForm.day_of_week = schedule?.day_of_week ?? 1;
    scheduleForm.start_time = schedule ? formatTime(schedule.start_time) : '08:00';
    scheduleForm.end_time = schedule ? formatTime(schedule.end_time) : '10:00';
    scheduleDialog.value = true;
};

const findSchedule = (id: number): Schedule | undefined =>
    props.group.class_sections.flatMap((section) => section.schedules).find((schedule) => schedule.id === id);

const onBlockSelected = (block: ScheduleBlock): void => {
    const schedule = findSchedule(block.id);

    if (schedule) {
        openSchedule(schedule);
    }
};

const submitSchedule = (): void => {
    const options = { preserveScroll: true, onSuccess: () => (scheduleDialog.value = false) };

    if (editingSchedule.value) {
        scheduleForm.put(admin.classSchedules.update(editingSchedule.value.id).url, options);
    } else {
        scheduleForm.post(admin.classSchedules.store().url, options);
    }
};

const destroySchedule = (): void => {
    if (!editingSchedule.value) {
        return;
    }

    const schedule = editingSchedule.value;
    scheduleDialog.value = false;

    confirmDelete(
        '¿Eliminar horario?',
        `Se eliminará el bloque de ${props.days[schedule.day_of_week]} ${formatTime(schedule.start_time)}–${formatTime(schedule.end_time)}.`,
        admin.classSchedules.destroy(schedule.id).url,
        { preserveState: true },
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Grupos', href: admin.groups.index() },
            { title: 'Detalle', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Grupo ${group.code}`" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            :title="`Grupo ${group.code}`"
            :description="`${group.cohort.academic_program.name} · Generación ${group.cohort.name} · Semestre ${group.period_number} · ${group.academic_period.name}`"
            section="Grupos y horarios"
            :icon="CalendarClock"
            :action-icon="Pencil"
            action-label="Editar grupo"
            :action-href="admin.groups.edit(group.id).url"
        />

        <!-- Horario semanal -->
        <AdminCard
            title="Horario semanal"
            description="Haz clic en un bloque para editarlo. Se validan choques de aula, docente y grupo."
            :icon="Clock"
        >
            <WeeklySchedule :blocks="blocks" :days="days" clickable @select="onBlockSelected" />

            <template #footer>
                <AdminButton :icon="Plus" :disabled="!group.class_sections.length" @click="openSchedule()">
                    Agregar horario
                </AdminButton>
            </template>
        </AdminCard>

        <div class="grid gap-5 xl:grid-cols-3">
            <!-- Materias -->
            <AdminCard
                class="xl:col-span-2"
                title="Materias y docentes"
                :description="`${group.class_sections.length} materias asignadas`"
                :icon="BookOpen"
                :padding="false"
            >
                <table v-if="group.class_sections.length" class="w-full border-collapse text-left text-sm">
                    <thead class="bg-[#F7F8FA] text-xs font-semibold uppercase tracking-wide text-[#383D43]">
                        <tr>
                            <th class="px-5 py-3 sm:px-6">Materia</th>
                            <th class="px-5 py-3 sm:px-6">Docente</th>
                            <th class="px-5 py-3 sm:px-6">Horas / semana</th>
                            <th class="px-5 py-3 text-right sm:px-6">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-[#E3E5E8]">
                        <tr v-for="section in group.class_sections" :key="section.id" class="hover:bg-[#FAF3F4]">
                            <td class="px-5 py-3 sm:px-6">
                                <p class="font-semibold text-[#292D32]">{{ section.subject.name }}</p>
                                <p class="text-xs text-[#858B93]">{{ section.subject.code }}</p>
                            </td>

                            <td class="px-5 py-3 text-[#525960] sm:px-6">
                                <template v-if="section.teacher">{{ fullName(section.teacher.people) }}</template>
                                <span v-else class="text-xs italic text-[#858B93]">Sin docente</span>
                            </td>

                            <td class="px-5 py-3 text-[#525960] sm:px-6">{{ weeklyHours(section) }}</td>

                            <td class="px-5 py-3 sm:px-6">
                                <div class="flex justify-end gap-1.5">
                                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F9ECEE]" title="Agregar horario" @click="openSchedule(null, section.id)">
                                        <Clock class="h-4 w-4" />
                                    </button>
                                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-[#383D43] hover:bg-[#F9ECEE] hover:text-[#941B2B]" title="Cambiar docente" @click="openSection(section)">
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F9ECEE]" title="Quitar" @click="destroySection(section)">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <EmptyState
                    v-else
                    :icon="BookOpen"
                    title="Sin materias"
                    description="Asigna las materias del plan que cursará este grupo."
                />

                <template #footer>
                    <AdminButton variant="secondary" :icon="Plus" :disabled="!availableSubjects.length" @click="openSection()">
                        Asignar materia
                    </AdminButton>
                </template>
            </AdminCard>

            <!-- Alumnos -->
            <AdminCard
                :title="`Alumnos (${group.students.length}${group.capacity ? ` / ${group.capacity}` : ''})`"
                :icon="Users"
                :padding="false"
            >
                <ul v-if="group.students.length" class="max-h-[480px] divide-y divide-[#E3E5E8] overflow-y-auto">
                    <li v-for="student in group.students" :key="student.id" class="flex items-center gap-3 px-5 py-3 sm:px-6">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#F9ECEE] text-[#941B2B]">
                            <GraduationCap class="h-4 w-4" />
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-[#292D32]">{{ fullName(student.people) }}</p>
                            <p class="text-xs text-[#858B93]">{{ student.registration_number }}</p>
                        </div>
                    </li>
                </ul>

                <EmptyState v-else :icon="Users" title="Sin alumnos" description="Agrega alumnos editando el grupo." />

                <template #footer>
                    <Link :href="admin.groups.edit(group.id).url" class="text-sm font-semibold text-[#941B2B] hover:underline">
                        Administrar alumnos
                    </Link>
                </template>
            </AdminCard>
        </div>

        <!-- Diálogo: materia -->
        <FormDialog
            v-model:open="sectionDialog"
            :title="editingSection ? `Docente de ${editingSection.subject.name}` : 'Asignar materia'"
            :loading="sectionForm.processing"
            @submit="submitSection"
        >
            <div v-if="!editingSection" class="md:col-span-2">
                <FormSelect
                    id="section_subject"
                    v-model="sectionForm.subject_id"
                    label="Materia del plan"
                    :options="availableSubjects.map((s) => ({ value: s.id, label: `${s.code} · ${s.name}` }))"
                    :error="sectionForm.errors.subject_id"
                    help="Materias del plan de la generación para este semestre."
                    required
                />
            </div>

            <div class="md:col-span-2">
                <FormSelect
                    id="section_teacher"
                    v-model="sectionForm.teacher_profile_id"
                    label="Docente"
                    placeholder="Sin docente por ahora"
                    :options="teacherOptions"
                    :error="sectionForm.errors.teacher_profile_id"
                    nullable
                />
            </div>
        </FormDialog>

        <!-- Diálogo: horario -->
        <FormDialog
            v-model:open="scheduleDialog"
            :title="editingSchedule ? 'Editar horario' : 'Nuevo horario'"
            :loading="scheduleForm.processing"
            @submit="submitSchedule"
        >
            <div class="md:col-span-2">
                <FormSelect
                    id="schedule_section"
                    v-model="scheduleForm.class_section_id"
                    label="Materia"
                    :options="sectionOptions"
                    :error="scheduleForm.errors.class_section_id"
                    required
                />
            </div>

            <FormSelect
                id="schedule_day"
                v-model="scheduleForm.day_of_week"
                label="Día"
                :options="dayOptions"
                :error="scheduleForm.errors.day_of_week"
                required
            />

            <FormSelect
                id="schedule_space"
                v-model="scheduleForm.space_id"
                label="Aula o espacio"
                placeholder="Sin aula"
                :options="spaceOptions"
                :error="scheduleForm.errors.space_id"
                nullable
            />

            <FormInput
                id="schedule_start"
                v-model="scheduleForm.start_time"
                type="time"
                label="Hora de inicio"
                :error="scheduleForm.errors.start_time"
                required
            />

            <FormInput
                id="schedule_end"
                v-model="scheduleForm.end_time"
                type="time"
                label="Hora de término"
                :error="scheduleForm.errors.end_time"
                required
            />

            <div v-if="editingSchedule" class="md:col-span-2">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#941B2B] hover:underline"
                    @click="destroySchedule"
                >
                    <Trash2 class="h-4 w-4" /> Eliminar este horario
                </button>
            </div>
        </FormDialog>
    </div>
</template>
