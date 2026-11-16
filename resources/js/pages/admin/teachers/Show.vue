<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Award,
    BookOpen,
    BriefcaseBusiness,
    CalendarClock,
    Clock,
    GraduationCap,
    Info,
    Pencil,
    Save,
    Stethoscope,
    UserRoundCheck,
} from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

import AdminButton from '@/components/admin/AdminButton.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import AttachmentsCard from '@/components/admin/AttachmentsCard.vue';
import DetailItem from '@/components/admin/DetailItem.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import TeacherRecordList from '@/components/admin/TeacherRecordList.vue';
import WeeklySchedule from '@/components/admin/WeeklySchedule.vue';
import { confirmDelete, enumOptions, formatDate, formatTime, fullName, label, toDateInput } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Attachment, Days, Option, PersonName, ScheduleBlock } from '@/types/admin';

type RecordType = 'degrees' | 'certifications' | 'experiences' | 'availabilities';

interface Degree { id: number; level: string; title: string; institution: string; license_number: string | null; obtained_at: string | null }
interface Certification { id: number; name: string; issuer: string | null; folio: string | null; issued_at: string | null; expires_at: string | null }
interface Experience { id: number; institution: string; position: string; started_at: string | null; ended_at: string | null; description: string | null }
interface Availability { id: number; academic_period_id: number | null; day_of_week: number; start_time: string; end_time: string; academic_period: Option | null }

const props = defineProps<{
    teacher: {
        id: number;
        employee_number: string | null;
        professional_license: string | null;
        professional_title: string | null;
        years_experience: number | null;
        status: string;
        professional_biography: string | null;
        people: PersonName & { id: number };
        specialties: Option[];
        knowledge_areas: Option[];
        degrees: Degree[];
        certifications: Certification[];
        experiences: Experience[];
        availabilities: Availability[];
        academic_programs: (Option & { pivot: { role: string } })[];
        class_sections: {
            id: number;
            subject: { id: number; code: string; name: string };
            group: { id: number; code: string; academic_period: Option };
            schedules: { id: number; day_of_week: number; start_time: string; end_time: string; space: { code: string; name: string } | null }[];
        }[];
        attachments: Attachment[];
    };
    days: Days;
    degreeLevels: string[];
    specialties: Option[];
    knowledgeAreas: Option[];
}>();

const dayOptions = computed(() =>
    Object.entries(props.days).map(([value, name]) => ({ value: Number(value), label: name })),
);

const classBlocks = computed<ScheduleBlock[]>(() =>
    props.teacher.class_sections.flatMap((section) =>
        section.schedules.map((schedule) => ({
            id: schedule.id,
            day_of_week: schedule.day_of_week,
            start_time: schedule.start_time,
            end_time: schedule.end_time,
            title: section.subject.name,
            subtitle: `Grupo ${section.group.code}`,
            meta: schedule.space ? schedule.space.code : undefined,
            colorKey: section.subject.id,
        })),
    ),
);

/* ---------------------- Especialidades y áreas ---------------------- */

const areasForm = useForm({
    specialty_ids: props.teacher.specialties.map((s) => s.id),
    knowledge_area_ids: props.teacher.knowledge_areas.map((a) => a.id),
});

const toggle = (list: number[], id: number): void => {
    const index = list.indexOf(id);
    index === -1 ? list.push(id) : list.splice(index, 1);
};

const saveAreas = (): void => {
    areasForm.put(admin.teachers.areas.update(props.teacher.id).url, { preserveScroll: true });
};

/* --------------------------- Expediente --------------------------- */

const dialogOpen = ref(false);
const recordType = ref<RecordType>('degrees');
const editingId = ref<number | null>(null);

const emptyRecord = {
    level: 'especialidad', title: '', institution: '', license_number: '', obtained_at: '',
    name: '', issuer: '', folio: '', issued_at: '', expires_at: '',
    position: '', started_at: '', ended_at: '', description: '',
    academic_period_id: null as number | null, day_of_week: 1 as number | null, start_time: '08:00', end_time: '14:00',
};

const recordForm = useForm({ ...emptyRecord });

const FIELDS: Record<RecordType, (keyof typeof emptyRecord)[]> = {
    degrees: ['level', 'title', 'institution', 'license_number', 'obtained_at'],
    certifications: ['name', 'issuer', 'folio', 'issued_at', 'expires_at'],
    experiences: ['institution', 'position', 'started_at', 'ended_at', 'description'],
    availabilities: ['academic_period_id', 'day_of_week', 'start_time', 'end_time'],
};

const TITLES: Record<RecordType, string> = {
    degrees: 'grado académico',
    certifications: 'certificación',
    experiences: 'experiencia laboral',
    availabilities: 'horario de disponibilidad',
};

const sources = reactive({
    degrees: computed(() => props.teacher.degrees),
    certifications: computed(() => props.teacher.certifications),
    experiences: computed(() => props.teacher.experiences),
    availabilities: computed(() => props.teacher.availabilities),
});

const openRecord = (type: RecordType, id: number | null = null): void => {
    recordType.value = type;
    editingId.value = id;
    recordForm.defaults({ ...emptyRecord });
    recordForm.reset();
    recordForm.clearErrors();

    const record = id ? (sources[type] as { id: number }[]).find((item) => item.id === id) : null;

    if (record) {
        FIELDS[type].forEach((field) => {
            const value = (record as Record<string, unknown>)[field];

            (recordForm as unknown as Record<string, unknown>)[field] = field.endsWith('_at')
                ? toDateInput(value as string | null)
                : field.endsWith('_time')
                  ? formatTime(value as string)
                  : (value ?? '');
        });
    }

    dialogOpen.value = true;
};

const submitRecord = (): void => {
    const type = recordType.value;
    const options = { preserveScroll: true, onSuccess: () => (dialogOpen.value = false) };

    recordForm.transform((data) =>
        Object.fromEntries(FIELDS[type].map((field) => [field, data[field] === '' ? null : data[field]])),
    );

    if (editingId.value) {
        recordForm.put(
            admin.teachers.records.update({ teacher: props.teacher.id, type, record: editingId.value }).url,
            options,
        );
    } else {
        recordForm.post(admin.teachers.records.store({ teacher: props.teacher.id, type }).url, options);
    }
};

const removeRecord = (type: RecordType, id: number): void => {
    confirmDelete(
        `¿Eliminar ${TITLES[type]}?`,
        'Esta acción no se puede deshacer.',
        admin.teachers.records.destroy({ teacher: props.teacher.id, type, record: id }).url,
        { preserveState: true },
    );
};

const range = (from: string | null, to: string | null) =>
    from || to ? `${formatDate(from)} – ${to ? formatDate(to) : 'actual'}` : undefined;

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Docentes', href: admin.teachers.index() },
            { title: 'Expediente', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="fullName(teacher.people)" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            :title="fullName(teacher.people)"
            :description="teacher.professional_title ?? 'Docente'"
            section="Docentes"
            :icon="UserRoundCheck"
            :action-icon="Pencil"
            action-label="Editar datos personales"
            :action-href="admin.people.edit(teacher.people.id).url"
        >
            <template #details>
                <div class="mt-2"><StatusBadge :value="teacher.status" /></div>
            </template>
        </PageHeader>

        <div class="grid gap-5 xl:grid-cols-3">
            <div class="flex flex-col gap-5 xl:col-span-2">
                <AdminCard title="Información profesional" :icon="Info">
                    <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <DetailItem label="No. de empleado" :value="teacher.employee_number" />
                        <DetailItem label="Cédula profesional" :value="teacher.professional_license" />
                        <DetailItem label="Años de experiencia" :value="teacher.years_experience" />
                        <DetailItem label="Teléfono" :value="teacher.people.phone" />
                    </dl>

                    <p v-if="teacher.professional_biography" class="mt-5 whitespace-pre-line text-sm leading-6 text-[#525960]">
                        {{ teacher.professional_biography }}
                    </p>
                </AdminCard>

                <AdminCard title="Horario de clases" description="Materias que imparte en los grupos." :icon="CalendarClock">
                    <WeeklySchedule :blocks="classBlocks" :days="days" />
                </AdminCard>

                <div class="grid gap-5 lg:grid-cols-2">
                    <TeacherRecordList
                        title="Títulos y grados"
                        :icon="GraduationCap"
                        :items="teacher.degrees.map((d) => ({
                            id: d.id,
                            title: d.title,
                            subtitle: `${label(d.level)} · ${d.institution}`,
                            meta: [d.license_number ? `Cédula ${d.license_number}` : null, d.obtained_at ? formatDate(d.obtained_at) : null].filter(Boolean).join(' · '),
                        }))"
                        empty-text="Sin grados registrados."
                        add-label="Agregar grado"
                        @add="openRecord('degrees')"
                        @edit="(id) => openRecord('degrees', id)"
                        @remove="(id) => removeRecord('degrees', id)"
                    />

                    <TeacherRecordList
                        title="Certificaciones"
                        :icon="Award"
                        :items="teacher.certifications.map((c) => ({
                            id: c.id,
                            title: c.name,
                            subtitle: c.issuer ?? undefined,
                            meta: range(c.issued_at, c.expires_at),
                        }))"
                        empty-text="Sin certificaciones registradas."
                        add-label="Agregar certificación"
                        @add="openRecord('certifications')"
                        @edit="(id) => openRecord('certifications', id)"
                        @remove="(id) => removeRecord('certifications', id)"
                    />

                    <TeacherRecordList
                        title="Experiencia laboral"
                        :icon="BriefcaseBusiness"
                        :items="teacher.experiences.map((e) => ({
                            id: e.id,
                            title: e.position,
                            subtitle: e.institution,
                            meta: range(e.started_at, e.ended_at),
                        }))"
                        empty-text="Sin experiencia registrada."
                        add-label="Agregar experiencia"
                        @add="openRecord('experiences')"
                        @edit="(id) => openRecord('experiences', id)"
                        @remove="(id) => removeRecord('experiences', id)"
                    />

                    <TeacherRecordList
                        title="Disponibilidad"
                        :icon="Clock"
                        :items="teacher.availabilities.map((a) => ({
                            id: a.id,
                            title: `${days[a.day_of_week]} ${formatTime(a.start_time)} – ${formatTime(a.end_time)}`,
                            subtitle: a.academic_period?.name ?? 'Todos los periodos',
                        }))"
                        empty-text="Sin horarios de disponibilidad."
                        add-label="Agregar horario"
                        @add="openRecord('availabilities')"
                        @edit="(id) => openRecord('availabilities', id)"
                        @remove="(id) => removeRecord('availabilities', id)"
                    />
                </div>
            </div>

            <div class="flex flex-col gap-5">
                <AdminCard title="Especialidades y áreas" :icon="Stethoscope">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-[#858B93]">Especialidades</p>
                    <div class="mb-5 flex flex-wrap gap-2">
                        <button
                            v-for="specialty in specialties"
                            :key="specialty.id"
                            type="button"
                            :class="[
                                'rounded-full border px-3 py-1.5 text-xs font-semibold transition',
                                areasForm.specialty_ids.includes(specialty.id)
                                    ? 'border-[#941B2B] bg-[#941B2B] text-white'
                                    : 'border-[#E3E5E8] bg-white text-[#383D43] hover:border-[#E8C7CC]',
                            ]"
                            @click="toggle(areasForm.specialty_ids, specialty.id)"
                        >
                            {{ specialty.name }}
                        </button>
                    </div>

                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-[#858B93]">Áreas de conocimiento</p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="area in knowledgeAreas"
                            :key="area.id"
                            type="button"
                            :class="[
                                'rounded-full border px-3 py-1.5 text-xs font-semibold transition',
                                areasForm.knowledge_area_ids.includes(area.id)
                                    ? 'border-[#941B2B] bg-[#941B2B] text-white'
                                    : 'border-[#E3E5E8] bg-white text-[#383D43] hover:border-[#E8C7CC]',
                            ]"
                            @click="toggle(areasForm.knowledge_area_ids, area.id)"
                        >
                            {{ area.name }}
                        </button>
                        <Link v-if="!knowledgeAreas.length" :href="admin.knowledgeAreas.index().url" class="text-xs text-[#941B2B] hover:underline">
                            Registrar áreas de conocimiento
                        </Link>
                    </div>

                    <template #footer>
                        <AdminButton :icon="Save" :loading="areasForm.processing" loading-text="Guardando..." @click="saveAreas">
                            Guardar
                        </AdminButton>
                    </template>
                </AdminCard>

                <AdminCard title="Programas académicos" :icon="BookOpen">
                    <ul v-if="teacher.academic_programs.length" class="flex flex-col gap-3">
                        <li v-for="program in teacher.academic_programs" :key="`${program.id}-${program.pivot.role}`">
                            <Link :href="admin.academicPrograms.show(program.id).url" class="group block">
                                <p class="text-sm font-semibold text-[#292D32] group-hover:text-[#941B2B]">{{ program.name }}</p>
                                <p class="text-xs text-[#858B93]">{{ label(program.pivot.role) }}</p>
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="text-sm italic text-[#858B93]">
                        Se asigna desde la edición de cada programa académico.
                    </p>
                </AdminCard>

                <AttachmentsCard
                    attachable-type="teacher_profile"
                    :attachable-id="teacher.id"
                    :attachments="teacher.attachments"
                    title="Documentación profesional"
                    description="Títulos, cédulas, certificados y constancias."
                />
            </div>
        </div>

        <!-- Diálogo del expediente -->
        <FormDialog
            v-model:open="dialogOpen"
            :title="`${editingId ? 'Editar' : 'Agregar'} ${TITLES[recordType]}`"
            :loading="recordForm.processing"
            @submit="submitRecord"
        >
            <template v-if="recordType === 'degrees'">
                <FormSelect id="degree_level" v-model="recordForm.level" label="Nivel" :options="enumOptions(degreeLevels)" :error="recordForm.errors.level" required />
                <FormInput id="degree_obtained" v-model="recordForm.obtained_at" type="date" label="Fecha de obtención" :error="recordForm.errors.obtained_at" />
                <div class="md:col-span-2">
                    <FormInput id="degree_title" v-model="recordForm.title" label="Título" placeholder="Ej. Especialista en Ortodoncia" :error="recordForm.errors.title" required />
                </div>
                <FormInput id="degree_institution" v-model="recordForm.institution" label="Institución" :error="recordForm.errors.institution" required />
                <FormInput id="degree_license" v-model="recordForm.license_number" label="Cédula" :error="recordForm.errors.license_number" />
            </template>

            <template v-else-if="recordType === 'certifications'">
                <div class="md:col-span-2">
                    <FormInput id="cert_name" v-model="recordForm.name" label="Certificación" :error="recordForm.errors.name" required />
                </div>
                <FormInput id="cert_issuer" v-model="recordForm.issuer" label="Emitida por" :error="recordForm.errors.issuer" />
                <FormInput id="cert_folio" v-model="recordForm.folio" label="Folio" :error="recordForm.errors.folio" />
                <FormInput id="cert_issued" v-model="recordForm.issued_at" type="date" label="Fecha de emisión" :error="recordForm.errors.issued_at" />
                <FormInput id="cert_expires" v-model="recordForm.expires_at" type="date" label="Vigencia" :error="recordForm.errors.expires_at" />
            </template>

            <template v-else-if="recordType === 'experiences'">
                <FormInput id="exp_position" v-model="recordForm.position" label="Puesto" :error="recordForm.errors.position" required />
                <FormInput id="exp_institution" v-model="recordForm.institution" label="Institución" :error="recordForm.errors.institution" required />
                <FormInput id="exp_started" v-model="recordForm.started_at" type="date" label="Inicio" :error="recordForm.errors.started_at" />
                <FormInput id="exp_ended" v-model="recordForm.ended_at" type="date" label="Fin" help="Vacío si es el empleo actual." :error="recordForm.errors.ended_at" />
                <div class="md:col-span-2">
                    <FormTextarea id="exp_description" v-model="recordForm.description" label="Descripción" :rows="3" :error="recordForm.errors.description" />
                </div>
            </template>

            <template v-else>
                <FormSelect id="avail_day" v-model="recordForm.day_of_week" label="Día" :options="dayOptions" :error="recordForm.errors.day_of_week" required />
                <div />
                <FormInput id="avail_start" v-model="recordForm.start_time" type="time" label="Desde" :error="recordForm.errors.start_time" required />
                <FormInput id="avail_end" v-model="recordForm.end_time" type="time" label="Hasta" :error="recordForm.errors.end_time" required />
            </template>
        </FormDialog>
    </div>
</template>
