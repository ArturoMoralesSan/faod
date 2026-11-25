<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CalendarCheck, ClipboardCheck, Info, Pencil, Plus, Save, Trash2, TriangleAlert } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import AdminButton from '@/components/admin/AdminButton.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import DetailItem from '@/components/admin/DetailItem.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import SeverityBadge from '@/components/admin/SeverityBadge.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { formatDate, formatDateTime, formatTime, fullName, label } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option, PersonName, SelectOption, StudentOption } from '@/types/admin';

interface SessionStudent {
    id: number;
    registration_number: string;
    people: PersonName | null;
    pivot: { attendance: string; notes: string | null };
}

const props = defineProps<{
    session: {
        id: number;
        date: string;
        start_time: string;
        end_time: string;
        status: string;
        topic: string | null;
        notes: string | null;
        clinic: Option;
        space: { id: number; code: string; name: string } | null;
        academic_period: Option | null;
        group: { id: number; code: string } | null;
        subject: { code: string; name: string } | null;
        supervisor: { people: PersonName | null } | null;
        creator: { name: string } | null;
        students: SessionStudent[];
        incidents: { id: number; title: string; type: string; severity: string; status: string; occurred_at: string }[];
    };
    attendanceOptions: string[];
    statuses: string[];
    availableStudents: StudentOption[];
}>();

/* ------------------------------ Asistencia ------------------------------ */

interface Row {
    student_profile_id: number;
    name: string;
    registration_number: string;
    attendance: string;
    notes: string;
}

const toRow = (s: SessionStudent): Row => ({
    student_profile_id: s.id,
    name: fullName(s.people),
    registration_number: s.registration_number,
    attendance: s.pivot.attendance,
    notes: s.pivot.notes ?? '',
});

const form = useForm<{ students: Row[] }>({
    students: props.session.students.map(toRow).sort((a, b) => a.name.localeCompare(b.name)),
});

const newStudent = ref('');

const studentOptions = computed<SelectOption[]>(() =>
    props.availableStudents
        .filter((s) => !form.students.some((row) => row.student_profile_id === s.id))
        .map((s) => ({ value: s.id, label: `${s.registration_number} · ${fullName(s.people)}` })),
);

const addStudent = (): void => {
    const student = props.availableStudents.find((s) => String(s.id) === newStudent.value);

    if (student) {
        form.students.push({
            student_profile_id: student.id,
            name: fullName(student.people),
            registration_number: student.registration_number,
            attendance: 'pendiente',
            notes: '',
        });
    }

    newStudent.value = '';
};

const markAll = (attendance: string): void => {
    form.students.forEach((row) => (row.attendance = attendance));
};

const summary = computed(() =>
    props.attendanceOptions.map((option) => ({
        option,
        count: form.students.filter((row) => row.attendance === option).length,
    })),
);

const saveAttendance = (): void => {
    form.transform((data) => ({
        students: data.students.map((row) => ({
            student_profile_id: row.student_profile_id,
            attendance: row.attendance,
            notes: row.notes || null,
        })),
    })).put(admin.clinicalSessions.attendance(props.session.id).url, { preserveScroll: true });
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Sesiones clínicas', href: admin.clinicalSessions.index() },
            { title: 'Detalle', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Sesión ${formatDate(session.date)}`" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            :title="session.topic ?? 'Sesión clínica'"
            :description="`${formatDate(session.date)} · ${formatTime(session.start_time)} – ${formatTime(session.end_time)} · ${session.clinic.name}`"
            section="Sesiones clínicas"
            :icon="CalendarCheck"
            :action-icon="Pencil"
            action-label="Editar sesión"
            :action-href="admin.clinicalSessions.edit(session.id).url"
        >
            <template #details>
                <div class="mt-2"><StatusBadge :value="session.status" /></div>
            </template>
        </PageHeader>

        <AdminCard title="Información de la sesión" :icon="Info">
            <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <DetailItem label="Clínica">
                    <Link :href="admin.clinics.show(session.clinic.id).url" class="text-sm font-semibold text-[#941B2B] hover:underline">
                        {{ session.clinic.name }}
                    </Link>
                </DetailItem>
                <DetailItem label="Consultorio" :value="session.space ? `${session.space.code} · ${session.space.name}` : null" />
                <DetailItem label="Supervisor" :value="session.supervisor ? fullName(session.supervisor.people) : null" />
                <DetailItem label="Periodo" :value="session.academic_period?.name" />
                <DetailItem label="Grupo">
                    <Link v-if="session.group" :href="admin.groups.show(session.group.id).url" class="text-sm font-semibold text-[#941B2B] hover:underline">
                        {{ session.group.code }}
                    </Link>
                    <span v-else class="text-sm text-[#858B93]">—</span>
                </DetailItem>
                <DetailItem label="Asignatura" :value="session.subject ? `${session.subject.code} · ${session.subject.name}` : null" />
                <DetailItem label="Registró" :value="session.creator?.name" />
            </dl>

            <p v-if="session.notes" class="mt-5 whitespace-pre-line text-sm leading-6 text-[#525960]">{{ session.notes }}</p>
        </AdminCard>

        <div class="grid gap-5 xl:grid-cols-3">
            <AdminCard title="Lista de asistencia" :icon="ClipboardCheck" class="xl:col-span-2">
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    <span
                        v-for="item in summary"
                        :key="item.option"
                        class="rounded-full border border-[#E3E5E8] bg-white px-3 py-1 text-xs font-semibold text-[#525960]"
                    >
                        {{ label(item.option) }}: {{ item.count }}
                    </span>

                    <span class="flex-1" />

                    <button
                        v-if="form.students.length"
                        type="button"
                        class="text-xs font-semibold text-[#941B2B] hover:underline"
                        @click="markAll('asistio')"
                    >
                        Marcar todos como asistió
                    </button>
                </div>

                <div v-if="form.students.length" class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-[#E3E5E8] text-xs uppercase tracking-wide text-[#858B93]">
                                <th class="py-2 pr-3 font-semibold">Alumno</th>
                                <th class="py-2 pr-3 font-semibold">Asistencia</th>
                                <th class="py-2 pr-3 font-semibold">Notas</th>
                                <th class="py-2" />
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E3E5E8]">
                            <tr v-for="(row, index) in form.students" :key="row.student_profile_id">
                                <td class="py-2 pr-3">
                                    <p class="font-semibold text-[#292D32]">{{ row.name }}</p>
                                    <p class="text-xs text-[#858B93]">{{ row.registration_number }}</p>
                                </td>
                                <td class="py-2 pr-3">
                                    <select v-model="row.attendance" class="form-select h-9 min-w-36 py-1 text-sm">
                                        <option v-for="option in attendanceOptions" :key="option" :value="option">{{ label(option) }}</option>
                                    </select>
                                </td>
                                <td class="py-2 pr-3">
                                    <input v-model="row.notes" type="text" maxlength="255" class="form-input h-9 py-1 text-sm" placeholder="Opcional" />
                                </td>
                                <td class="py-2 text-right">
                                    <button
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F1F2F4]"
                                        title="Quitar de la sesión"
                                        @click="form.students.splice(index, 1)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p v-else class="text-sm italic text-[#858B93]">
                    Sin alumnos. Asigna un grupo a la sesión o agrégalos uno por uno.
                </p>

                <p v-if="Object.keys(form.errors).length" class="form-error mt-2">
                    Revisa la lista: {{ Object.values(form.errors)[0] }}
                </p>

                <template #footer>
                    <div class="flex w-full flex-wrap items-center gap-3">
                        <div class="min-w-64 flex-1">
                            <FilterSelect v-model="newStudent" placeholder="Agregar alumno..." :options="studentOptions" @change="addStudent" />
                        </div>

                        <AdminButton :icon="Save" :loading="form.processing" loading-text="Guardando..." @click="saveAttendance">
                            Guardar asistencia
                        </AdminButton>
                    </div>
                </template>
            </AdminCard>

            <AdminCard title="Incidencias" :icon="TriangleAlert">
                <ul v-if="session.incidents.length" class="flex flex-col gap-3">
                    <li v-for="incident in session.incidents" :key="incident.id">
                        <Link :href="admin.clinicIncidents.show(incident.id).url" class="group flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-[#292D32] group-hover:text-[#292D32]">{{ incident.title }}</p>
                                <p class="text-xs text-[#858B93]">{{ label(incident.status) }} · {{ formatDateTime(incident.occurred_at) }}</p>
                            </div>
                            <SeverityBadge :value="incident.severity" />
                        </Link>
                    </li>
                </ul>
                <p v-else class="text-sm italic text-[#858B93]">Sin incidencias en esta sesión.</p>

                <template #footer>
                    <Link
                        :href="admin.clinicIncidents.create({ query: { clinic_id: session.clinic.id, clinical_session_id: session.id, space_id: session.space?.id } }).url"
                        class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#941B2B] hover:underline"
                    >
                        <Plus class="h-4 w-4" /> Reportar incidencia
                    </Link>
                </template>
            </AdminCard>
        </div>
    </div>
</template>
