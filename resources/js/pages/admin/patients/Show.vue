<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Activity,
    CalendarRange,
    ClipboardCheck,
    ClipboardList,
    ClipboardPlus,
    Contact,
    Smile,
    Stethoscope,
    HeartPulse,
    Info,
    NotebookPen,
    Pencil,
    Phone,
    Pill,
    Plus,
    Trash2,
    TriangleAlert,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

import AdminCard from '@/components/admin/AdminCard.vue';
import AttachmentsCard from '@/components/admin/AttachmentsCard.vue';
import Odontogram from '@/components/admin/Odontogram.vue';
import ReviewDialog from '@/components/admin/ReviewDialog.vue';
import DetailItem from '@/components/admin/DetailItem.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import RecordList from '@/components/admin/TeacherRecordList.vue';
import {
    age,
    confirmDelete,
    enumOptions,
    formatDate,
    formatDateTime,
    formatMoney,
    formatTime,
    fullName,
    label,
    recordOptions,
    toDateInput,
    toDateTimeInput,
} from '@/lib/admin';
import admin from '@/routes/admin';
import type { Attachment, OdontogramEntryData, Option, PersonName, ReviewData, SelectOption, StudentOption, TeacherOption } from '@/types/admin';

type RecordType = 'contacts' | 'conditions' | 'allergies' | 'medications' | 'consultations' | 'notes';
type Person = { people: PersonName | null } | null;

interface Consultation {
    id: number;
    clinic_id: number | null;
    clinical_session_id: number | null;
    student_profile_id: number | null;
    teacher_profile_id: number | null;
    attended_at: string;
    reason: string;
    blood_pressure: string | null;
    heart_rate: number | null;
    respiratory_rate: number | null;
    temperature: number | null;
    weight: number | null;
    height: number | null;
    findings: string | null;
    notes: string | null;
    clinic: Option | null;
    student: Person;
    teacher: Person;
}

interface ProgressNote {
    id: number;
    patient_consultation_id: number | null;
    student_profile_id: number | null;
    teacher_profile_id: number | null;
    noted_at: string;
    note: string;
    student: Person;
    teacher: Person;
    creator: { name: string } | null;
}

interface Diagnosis {
    id: number;
    patient_consultation_id: number | null;
    diagnosis_id: number | null;
    description: string | null;
    tooth: string | null;
    surfaces: string | null;
    type: string;
    review_status: string;
    diagnosed_at: string;
    student_profile_id: number | null;
    teacher_profile_id: number | null;
    notes: string | null;
    diagnosis: { id: number; code: string; name: string } | null;
    student: Person;
    teacher: Person;
    reviews: ReviewData[];
}

const props = defineProps<{
    patient: {
        id: number;
        record_number: string;
        status: string;
        blood_type: string | null;
        occupation: string | null;
        marital_status: string | null;
        referred_by: string | null;
        registered_at: string;
        notes: string | null;
        clinic: Option | null;
        creator: { name: string } | null;
        people: PersonName & {
            curp: string | null;
            birth_date: string | null;
            gender: string | null;
            street: string | null;
            exterior_number: string | null;
            neighborhood: string | null;
            city: string | null;
            state: string | null;
        };
        emergency_contacts: { id: number; name: string; relationship: string | null; phone: string; alternate_phone: string | null }[];
        conditions: { id: number; category: string; name: string; notes: string | null }[];
        allergies: { id: number; allergen: string; reaction: string | null; severity: string }[];
        medications: { id: number; name: string; dose: string | null; frequency: string | null; started_at: string | null; ended_at: string | null; notes: string | null }[];
        consultations: Consultation[];
        progress_notes: ProgressNote[];
        diagnoses: Diagnosis[];
        treatment_plans: {
            id: number;
            title: string;
            status: string;
            review_status: string;
            started_at: string;
            procedures_count: number;
            done_count: number;
            total_cost: number | string | null;
            teacher: Person;
        }[];
        odontogram_entries: OdontogramEntryData[];
        appointments: {
            id: number;
            date: string;
            start_time: string;
            end_time: string;
            status: string;
            reason: string | null;
            clinic: Option;
            space: { code: string; name: string } | null;
            student: Person;
        }[];
        attachments: Attachment[];
    };
    diagnosisCatalog: { id: number; code: string; name: string; category: string | null }[];
    diagnosisTypes: string[];
    odontogram: {
        surfaces: string[];
        surfaceConditions: string[];
        toothConditions: string[];
        permanentTeeth: number[];
        primaryTeeth: number[];
    };
    can: { diagnose: boolean; review: boolean; treat: boolean; schedule: boolean; billing: boolean };
    conditionCategories: string[];
    allergySeverities: string[];
    clinics: Option[];
    sessions: { id: number; clinic_id: number; date: string; start_time: string; topic: string | null }[];
    students: StudentOption[];
    teachers: TeacherOption[];
}>();

const person = computed(() => props.patient.people);
const years = computed(() => age(person.value.birth_date));

const address = computed(() =>
    [
        [person.value.street, person.value.exterior_number].filter(Boolean).join(' '),
        person.value.neighborhood,
        person.value.city,
        person.value.state,
    ]
        .filter(Boolean)
        .join(', ') || null,
);

const conditionsByCategory = computed(() =>
    props.conditionCategories
        .map((category) => ({ category, items: props.patient.conditions.filter((c) => c.category === category) }))
        .filter((group) => group.items.length),
);

const studentOptions = computed<SelectOption[]>(() =>
    props.students.map((s) => ({ value: s.id, label: `${s.registration_number} · ${fullName(s.people)}` })),
);
const teacherOptions = computed<SelectOption[]>(() => props.teachers.map((t) => ({ value: t.id, label: fullName(t.people) })));
const consultationOptions = computed<SelectOption[]>(() =>
    props.patient.consultations.map((c) => ({ value: c.id, label: `${formatDateTime(c.attended_at)} · ${c.reason.substring(0, 50)}` })),
);

const vitals = (c: Consultation) =>
    [
        c.blood_pressure ? `TA ${c.blood_pressure}` : null,
        c.heart_rate ? `FC ${c.heart_rate}` : null,
        c.respiratory_rate ? `FR ${c.respiratory_rate}` : null,
        c.temperature ? `T ${c.temperature} °C` : null,
        c.weight ? `${c.weight} kg` : null,
        c.height ? `${c.height} m` : null,
    ]
        .filter(Boolean)
        .join(' · ');

/* --------------------------- Registros del expediente --------------------------- */

const dialogOpen = ref(false);
const recordType = ref<RecordType>('contacts');
const editingId = ref<number | null>(null);

const emptyRecord = {
    name: '', relationship: '', phone: '', alternate_phone: '',
    category: 'patologico', notes: '',
    allergen: '', reaction: '', severity: 'moderada',
    dose: '', frequency: '', started_at: '', ended_at: '',
    clinic_id: null as number | null, clinical_session_id: null as number | null,
    student_profile_id: null as number | null, teacher_profile_id: null as number | null,
    attended_at: '', reason: '', blood_pressure: '',
    heart_rate: null as number | null, respiratory_rate: null as number | null,
    temperature: null as number | null, weight: null as number | null, height: null as number | null,
    findings: '',
    patient_consultation_id: null as number | null, noted_at: '', note: '',
};

type Field = keyof typeof emptyRecord;

const recordForm = useForm({ ...emptyRecord });

const FIELDS: Record<RecordType, Field[]> = {
    contacts: ['name', 'relationship', 'phone', 'alternate_phone'],
    conditions: ['category', 'name', 'notes'],
    allergies: ['allergen', 'reaction', 'severity'],
    medications: ['name', 'dose', 'frequency', 'started_at', 'ended_at', 'notes'],
    consultations: [
        'clinic_id', 'clinical_session_id', 'student_profile_id', 'teacher_profile_id', 'attended_at', 'reason',
        'blood_pressure', 'heart_rate', 'respiratory_rate', 'temperature', 'weight', 'height', 'findings', 'notes',
    ],
    notes: ['patient_consultation_id', 'student_profile_id', 'teacher_profile_id', 'noted_at', 'note'],
};

const DATETIME_FIELDS: Field[] = ['attended_at', 'noted_at'];
const DATE_FIELDS: Field[] = ['started_at', 'ended_at'];

const TITLES: Record<RecordType, string> = {
    contacts: 'contacto de emergencia',
    conditions: 'antecedente',
    allergies: 'alergia',
    medications: 'medicamento',
    consultations: 'consulta',
    notes: 'nota de evolución',
};

const sources: Record<RecordType, () => { id: number }[]> = {
    contacts: () => props.patient.emergency_contacts,
    conditions: () => props.patient.conditions,
    allergies: () => props.patient.allergies,
    medications: () => props.patient.medications,
    consultations: () => props.patient.consultations,
    notes: () => props.patient.progress_notes,
};

const sessionOptions = computed<SelectOption[]>(() =>
    props.sessions
        .filter((s) => !recordForm.clinic_id || s.clinic_id === recordForm.clinic_id)
        .map((s) => ({ value: s.id, label: `${formatDate(s.date)} ${formatTime(s.start_time)}${s.topic ? ` · ${s.topic}` : ''}` })),
);

const openRecord = (type: RecordType, id: number | null = null): void => {
    recordType.value = type;
    editingId.value = id;
    recordForm.defaults({ ...emptyRecord });
    recordForm.reset();
    recordForm.clearErrors();

    const record = id ? sources[type]().find((item) => item.id === id) : null;
    const values = recordForm as unknown as Record<string, unknown>;

    if (record) {
        FIELDS[type].forEach((field) => {
            const value = (record as Record<string, unknown>)[field];

            values[field] = DATETIME_FIELDS.includes(field)
                ? toDateTimeInput(value as string | null)
                : DATE_FIELDS.includes(field)
                  ? toDateInput(value as string | null)
                  : (value ?? (typeof emptyRecord[field] === 'string' ? '' : null));
        });
    } else if (type === 'consultations') {
        recordForm.attended_at = toDateTimeInput(new Date().toISOString());
        recordForm.clinic_id = props.patient.clinic?.id ?? null;
    } else if (type === 'notes') {
        recordForm.noted_at = toDateTimeInput(new Date().toISOString());
        recordForm.patient_consultation_id = props.patient.consultations[0]?.id ?? null;
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
        recordForm.put(admin.patients.records.update({ patient: props.patient.id, type, record: editingId.value }).url, options);
    } else {
        recordForm.post(admin.patients.records.store({ patient: props.patient.id, type }).url, options);
    }
};

const removeRecord = (type: RecordType, id: number): void => {
    confirmDelete(
        `¿Eliminar ${TITLES[type]}?`,
        'Esta acción no se puede deshacer.',
        admin.patients.records.destroy({ patient: props.patient.id, type, record: id }).url,
        { preserveState: true },
    );
};

/* -------------------------------- Diagnósticos -------------------------------- */

const diagnosisTitle = (d: Diagnosis) =>
    [d.tooth ? `Diente ${d.tooth}${d.surfaces ? ` (${d.surfaces})` : ''}` : null, d.diagnosis ? `${d.diagnosis.code} · ${d.diagnosis.name}` : d.description]
        .filter(Boolean)
        .join(' · ');

const diagnosisOptions = computed<SelectOption[]>(() =>
    props.patient.diagnoses.map((d) => ({ value: d.id, label: diagnosisTitle(d) })),
);

const catalogOptions = computed<SelectOption[]>(() =>
    props.diagnosisCatalog.map((d) => ({ value: d.id, label: `${d.code} · ${d.name}` })),
);

const toothOptions = computed<SelectOption[]>(() =>
    [...props.odontogram.permanentTeeth, ...props.odontogram.primaryTeeth].map((t) => ({ value: String(t), label: String(t) })),
);

const diagnosisDialog = ref(false);
const editingDiagnosis = ref<number | null>(null);

const emptyDiagnosis = {
    diagnosis_id: null as number | null,
    description: '',
    tooth: '' as string,
    surfaces: [] as string[],
    type: 'presuntivo',
    diagnosed_at: '',
    patient_consultation_id: null as number | null,
    student_profile_id: null as number | null,
    teacher_profile_id: null as number | null,
    notes: '',
};

const diagnosisForm = useForm({ ...emptyDiagnosis });

const openDiagnosis = (id: number | null = null): void => {
    editingDiagnosis.value = id;
    diagnosisForm.defaults({ ...emptyDiagnosis, surfaces: [] });
    diagnosisForm.reset();
    diagnosisForm.clearErrors();

    const d = id ? props.patient.diagnoses.find((item) => item.id === id) : null;

    if (d) {
        Object.assign(diagnosisForm, {
            diagnosis_id: d.diagnosis_id,
            description: d.description ?? '',
            tooth: d.tooth ?? '',
            surfaces: d.surfaces ? d.surfaces.split('') : [],
            type: d.type,
            diagnosed_at: toDateInput(d.diagnosed_at),
            patient_consultation_id: d.patient_consultation_id,
            student_profile_id: d.student_profile_id,
            teacher_profile_id: d.teacher_profile_id,
            notes: d.notes ?? '',
        });
    } else {
        diagnosisForm.diagnosed_at = toDateInput(new Date().toISOString());
        diagnosisForm.patient_consultation_id = props.patient.consultations[0]?.id ?? null;
    }

    diagnosisDialog.value = true;
};

const toggleSurface = (surface: string): void => {
    const list = diagnosisForm.surfaces;
    const index = list.indexOf(surface);
    index === -1 ? list.push(surface) : list.splice(index, 1);
};

const submitDiagnosis = (): void => {
    const options = { preserveScroll: true, onSuccess: () => (diagnosisDialog.value = false) };

    diagnosisForm.transform((data) => ({
        ...data,
        description: data.description || null,
        tooth: data.tooth || null,
        surfaces: data.surfaces.join('') || null,
        notes: data.notes || null,
    }));

    if (editingDiagnosis.value) {
        diagnosisForm.put(admin.patients.diagnoses.update({ patient: props.patient.id, diagnosis: editingDiagnosis.value }).url, options);
    } else {
        diagnosisForm.post(admin.patients.diagnoses.store(props.patient.id).url, options);
    }
};

const removeDiagnosis = (id: number): void => {
    confirmDelete(
        '¿Eliminar diagnóstico?',
        'Se eliminará junto con su historial de revisiones.',
        admin.patients.diagnoses.destroy({ patient: props.patient.id, diagnosis: id }).url,
        { preserveState: true },
    );
};

const reviewOpen = ref(false);
const reviewing = ref<Diagnosis | null>(null);

const openReview = (d: Diagnosis): void => {
    reviewing.value = d;
    reviewOpen.value = true;
};

/* --------------------------- Planes de tratamiento --------------------------- */

const planDialog = ref(false);

const planForm = useForm({
    title: '',
    status: 'en_proceso',
    started_at: '',
    student_profile_id: null as number | null,
    teacher_profile_id: null as number | null,
    notes: '',
});

const openPlan = (): void => {
    planForm.reset();
    planForm.clearErrors();
    planForm.started_at = toDateInput(new Date().toISOString());
    dialogPlanTitle();
    planDialog.value = true;
};

// Propuesta de título a partir del diagnóstico más reciente.
const dialogPlanTitle = (): void => {
    const latest = props.patient.diagnoses[0];
    planForm.title = latest ? `Tratamiento: ${latest.diagnosis?.name ?? latest.description ?? ''}`.trim() : 'Plan de tratamiento integral';
};

const submitPlan = (): void => {
    planForm.transform((data) => ({ ...data, notes: data.notes || null }))
        .post(admin.patients.treatmentPlans.store(props.patient.id).url, { onSuccess: () => (planDialog.value = false) });
};

const money = formatMoney;

const ALLERGY_TONES: Record<string, string> = {
    leve: 'border-[#F1DDB0] bg-[#FDF6E7] text-[#8A5A00]',
    moderada: 'border-[#F5C9A8] bg-[#FDEFE4] text-[#A64B00]',
    grave: 'border-[#941B2B] bg-[#941B2B] text-white',
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Pacientes', href: admin.patients.index() },
            { title: 'Expediente', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="fullName(person)" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            :title="fullName(person)"
            :description="[patient.record_number, years !== null ? `${years} años` : null, person.gender ? label(person.gender) : null].filter(Boolean).join(' · ')"
            section="Pacientes"
            :icon="Contact"
            :action-icon="Pencil"
            action-label="Editar datos"
            :action-href="admin.patients.edit(patient.id).url"
        >
            <template #details>
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <StatusBadge :value="patient.status" />
                    <Link v-if="can.billing" :href="admin.finance.accounts.show({ type: 'patient', id: patient.id }).url" class="text-sm font-semibold text-[#941B2B] hover:underline">
                        Estado de cuenta
                    </Link>
                </div>
            </template>
        </PageHeader>

        <!-- Alerta de alergias -->
        <div
            v-if="patient.allergies.length"
            class="flex items-start gap-3 rounded-2xl border border-[#E8C7CC] bg-[#F9ECEE] p-4 text-[#941B2B]"
        >
            <TriangleAlert class="mt-0.5 h-5 w-5 shrink-0" />
            <div>
                <p class="text-sm font-bold">Alergias</p>
                <p class="text-sm">
                    {{ patient.allergies.map((a) => `${a.allergen} (${label(a.severity).toLowerCase()})`).join(' · ') }}
                </p>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-3">
            <div class="flex flex-col gap-5 xl:col-span-2">
                <AdminCard title="Datos generales" :icon="Info">
                    <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <DetailItem label="Fecha de nacimiento" :value="person.birth_date ? formatDate(person.birth_date) : null" />
                        <DetailItem label="CURP" :value="person.curp" />
                        <DetailItem label="Tipo de sangre" :value="patient.blood_type" />
                        <DetailItem label="Estado civil" :value="patient.marital_status ? label(patient.marital_status) : null" />
                        <DetailItem label="Teléfono" :value="person.phone" />
                        <DetailItem label="Correo" :value="person.personal_email" />
                        <DetailItem label="Ocupación" :value="patient.occupation" />
                        <DetailItem label="Referido por" :value="patient.referred_by" />
                        <div class="sm:col-span-2">
                            <DetailItem label="Domicilio" :value="address" />
                        </div>
                        <DetailItem label="Clínica" :value="patient.clinic?.name" />
                        <DetailItem label="Expediente abierto" :value="`${formatDate(patient.registered_at)}${patient.creator ? ` · ${patient.creator.name}` : ''}`" />
                    </dl>

                    <p v-if="patient.notes" class="mt-5 whitespace-pre-line text-sm leading-6 text-[#525960]">{{ patient.notes }}</p>
                </AdminCard>

                <!-- Odontograma -->
                <AdminCard title="Odontograma" :icon="Smile">
                    <Odontogram
                        :patient-id="patient.id"
                        :entries="patient.odontogram_entries"
                        :config="odontogram"
                        :diagnosis-options="diagnosisOptions"
                        :can-edit="can.diagnose"
                    />
                </AdminCard>

                <!-- Diagnósticos -->
                <AdminCard id="diagnosticos" title="Diagnósticos" description="Los registra el alumno y los autoriza el docente." :icon="Stethoscope">
                    <ul v-if="patient.diagnoses.length" class="flex flex-col gap-3">
                        <li v-for="d in patient.diagnoses" :key="d.id" class="rounded-xl border border-[#E3E5E8] p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-semibold text-[#292D32]">{{ diagnosisTitle(d) }}</p>
                                        <span class="rounded-full border border-[#E3E5E8] px-2 py-0.5 text-xs font-semibold text-[#525960]">{{ label(d.type) }}</span>
                                        <StatusBadge :value="d.review_status" />
                                    </div>
                                    <p v-if="d.diagnosis && d.description" class="mt-1 text-sm text-[#383D43]">{{ d.description }}</p>
                                    <p v-if="d.notes" class="mt-1 whitespace-pre-line text-xs text-[#525960]">{{ d.notes }}</p>
                                    <p class="mt-1 text-xs text-[#858B93]">
                                        {{ [formatDate(d.diagnosed_at), d.student ? fullName(d.student.people) : null, d.teacher ? `Revisa: ${fullName(d.teacher.people)}` : null].filter(Boolean).join(' · ') }}
                                    </p>
                                    <div v-if="d.reviews.length" class="mt-2 rounded-lg bg-[#F7F8FA] px-3 py-2 text-xs text-[#525960]">
                                        <p class="font-semibold">
                                            {{ label(d.reviews[0].decision) }} · {{ formatDateTime(d.reviews[0].created_at) }}<template v-if="d.reviews[0].reviewer"> · {{ d.reviews[0].reviewer.name }}</template>
                                        </p>
                                        <p v-if="d.reviews[0].comments" class="mt-0.5 whitespace-pre-line">{{ d.reviews[0].comments }}</p>
                                    </div>
                                </div>
                                <div class="flex shrink-0 gap-1">
                                    <button
                                        v-if="can.review && d.review_status !== 'autorizado'"
                                        type="button"
                                        class="inline-flex h-8 items-center gap-1 rounded-lg border border-[#BFE3CC] px-2 text-xs font-semibold text-[#187744] hover:bg-[#E2F3E8]"
                                        @click="openReview(d)"
                                    >
                                        <ClipboardCheck class="h-4 w-4" /> Revisar
                                    </button>
                                    <template v-if="can.diagnose">
                                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#383D43] hover:bg-[#F9ECEE] hover:text-[#941B2B]" title="Editar" @click="openDiagnosis(d.id)">
                                            <Pencil class="h-4 w-4" />
                                        </button>
                                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F9ECEE]" title="Eliminar" @click="removeDiagnosis(d.id)">
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="text-sm italic text-[#858B93]">Sin diagnósticos registrados.</p>

                    <template v-if="can.diagnose" #footer>
                        <button type="button" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#941B2B] hover:underline" @click="openDiagnosis()">
                            <Plus class="h-4 w-4" /> Registrar diagnóstico
                        </button>
                    </template>
                </AdminCard>

                <!-- Planes de tratamiento -->
                <AdminCard title="Planes de tratamiento" :icon="ClipboardPlus">
                    <ul v-if="patient.treatment_plans.length" class="flex flex-col gap-3">
                        <li v-for="plan in patient.treatment_plans" :key="plan.id">
                            <Link
                                :href="admin.treatmentPlans.show(plan.id).url"
                                class="flex items-center justify-between gap-3 rounded-xl border border-[#E3E5E8] p-4 transition hover:border-[#E8C7CC] hover:bg-[#FAF3F4]"
                            >
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-[#292D32]">{{ plan.title }}</p>
                                    <p class="text-xs text-[#858B93]">
                                        {{ formatDate(plan.started_at) }} · {{ plan.done_count }}/{{ plan.procedures_count }} procedimientos · {{ money(plan.total_cost) }}
                                        <template v-if="plan.teacher"> · {{ fullName(plan.teacher.people) }}</template>
                                    </p>
                                    <div class="mt-2 h-1.5 w-40 overflow-hidden rounded-full bg-[#F1F2F4]">
                                        <div class="h-full rounded-full bg-[#187744]" :style="{ width: `${plan.procedures_count ? (plan.done_count / plan.procedures_count) * 100 : 0}%` }" />
                                    </div>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-1">
                                    <StatusBadge :value="plan.status" />
                                    <StatusBadge :value="plan.review_status" />
                                </div>
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="text-sm italic text-[#858B93]">Sin planes de tratamiento.</p>

                    <template v-if="can.treat" #footer>
                        <button type="button" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#941B2B] hover:underline" @click="openPlan">
                            <Plus class="h-4 w-4" /> Nuevo plan de tratamiento
                        </button>
                    </template>
                </AdminCard>

                <!-- Consultas -->
                <AdminCard title="Consultas" description="Motivo, signos vitales y exploración de cada visita." :icon="HeartPulse">
                    <ul v-if="patient.consultations.length" class="flex flex-col gap-3">
                        <li v-for="c in patient.consultations" :key="c.id" class="rounded-xl border border-[#E3E5E8] p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-[#292D32]">{{ formatDateTime(c.attended_at) }}<template v-if="c.clinic"> · {{ c.clinic.name }}</template></p>
                                    <p class="mt-1 text-sm text-[#383D43]">{{ c.reason }}</p>
                                    <p v-if="vitals(c)" class="mt-1 text-xs font-medium text-[#525960]">{{ vitals(c) }}</p>
                                    <p v-if="c.findings" class="mt-2 whitespace-pre-line text-xs text-[#525960]">{{ c.findings }}</p>
                                    <p class="mt-2 text-xs text-[#858B93]">
                                        {{ [c.student ? `Atendió: ${fullName(c.student.people)}` : null, c.teacher ? `Supervisó: ${fullName(c.teacher.people)}` : null].filter(Boolean).join(' · ') }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 gap-1">
                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#383D43] hover:bg-[#F9ECEE] hover:text-[#941B2B]" title="Editar" @click="openRecord('consultations', c.id)">
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F9ECEE]" title="Eliminar" @click="removeRecord('consultations', c.id)">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="text-sm italic text-[#858B93]">Sin consultas registradas.</p>

                    <template #footer>
                        <button type="button" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#941B2B] hover:underline" @click="openRecord('consultations')">
                            <Plus class="h-4 w-4" /> Registrar consulta
                        </button>
                    </template>
                </AdminCard>

                <!-- Evolución -->
                <AdminCard title="Notas de evolución" :icon="NotebookPen">
                    <ol v-if="patient.progress_notes.length" class="relative ml-2 border-l border-[#E3E5E8]">
                        <li v-for="n in patient.progress_notes" :key="n.id" class="mb-5 ml-5 last:mb-0">
                            <span class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full border-2 border-white bg-[#941B2B]" />
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-[#858B93]">{{ formatDateTime(n.noted_at) }}</p>
                                    <p class="mt-1 whitespace-pre-line text-sm text-[#292D32]">{{ n.note }}</p>
                                    <p class="mt-1 text-xs text-[#858B93]">
                                        {{ [n.student ? fullName(n.student.people) : null, n.teacher ? `Supervisó: ${fullName(n.teacher.people)}` : null, n.creator ? `Capturó: ${n.creator.name}` : null].filter(Boolean).join(' · ') }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 gap-1">
                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#383D43] hover:bg-[#F9ECEE] hover:text-[#941B2B]" title="Editar" @click="openRecord('notes', n.id)">
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F9ECEE]" title="Eliminar" @click="removeRecord('notes', n.id)">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ol>
                    <p v-else class="text-sm italic text-[#858B93]">Sin notas de evolución.</p>

                    <template #footer>
                        <button type="button" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#941B2B] hover:underline" @click="openRecord('notes')">
                            <Plus class="h-4 w-4" /> Agregar nota
                        </button>
                    </template>
                </AdminCard>
            </div>

            <div class="flex flex-col gap-5">
                <!-- Próximas citas -->
                <AdminCard title="Próximas citas" :icon="CalendarRange">
                    <ul v-if="patient.appointments.length" class="flex flex-col gap-2">
                        <li v-for="a in patient.appointments" :key="a.id" class="flex items-center justify-between gap-2 rounded-xl border border-[#E3E5E8] p-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-[#292D32]">{{ formatDate(a.date) }} · {{ formatTime(a.start_time) }}</p>
                                <p class="truncate text-xs text-[#858B93]">{{ [a.clinic.name, a.space?.name, a.reason].filter(Boolean).join(' · ') }}</p>
                            </div>
                            <StatusBadge :value="a.status" />
                        </li>
                    </ul>
                    <p v-else class="text-sm italic text-[#858B93]">Sin citas próximas.</p>

                    <template v-if="can.schedule" #footer>
                        <Link :href="admin.appointments.create({ query: { patient_id: patient.id } }).url" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#941B2B] hover:underline">
                            <Plus class="h-4 w-4" /> Agendar cita
                        </Link>
                    </template>
                </AdminCard>

                <!-- Alergias -->
                <AdminCard title="Alergias" :icon="TriangleAlert">
                    <ul v-if="patient.allergies.length" class="flex flex-col gap-2">
                        <li v-for="a in patient.allergies" :key="a.id" class="flex items-center justify-between gap-2 rounded-xl border border-[#E3E5E8] p-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-[#292D32]">{{ a.allergen }}</p>
                                <p v-if="a.reaction" class="text-xs text-[#858B93]">{{ a.reaction }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <span :class="['rounded-full border px-2 py-0.5 text-xs font-semibold', ALLERGY_TONES[a.severity]]">{{ label(a.severity) }}</span>
                                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#383D43] hover:bg-[#F9ECEE]" title="Editar" @click="openRecord('allergies', a.id)">
                                    <Pencil class="h-4 w-4" />
                                </button>
                                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F9ECEE]" title="Eliminar" @click="removeRecord('allergies', a.id)">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="text-sm italic text-[#858B93]">Sin alergias conocidas.</p>

                    <template #footer>
                        <button type="button" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#941B2B] hover:underline" @click="openRecord('allergies')">
                            <Plus class="h-4 w-4" /> Agregar alergia
                        </button>
                    </template>
                </AdminCard>

                <!-- Antecedentes -->
                <AdminCard title="Antecedentes" :icon="ClipboardList">
                    <div v-if="conditionsByCategory.length" class="flex flex-col gap-4">
                        <div v-for="group in conditionsByCategory" :key="group.category">
                            <p class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-[#858B93]">{{ label(group.category) }}</p>
                            <ul class="flex flex-col gap-1.5">
                                <li v-for="c in group.items" :key="c.id" class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-[#292D32]">{{ c.name }}</p>
                                        <p v-if="c.notes" class="text-xs text-[#858B93]">{{ c.notes }}</p>
                                    </div>
                                    <div class="flex shrink-0 gap-1">
                                        <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-[#383D43] hover:bg-[#F9ECEE]" title="Editar" @click="openRecord('conditions', c.id)">
                                            <Pencil class="h-3.5 w-3.5" />
                                        </button>
                                        <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F9ECEE]" title="Eliminar" @click="removeRecord('conditions', c.id)">
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <p v-else class="text-sm italic text-[#858B93]">Sin antecedentes registrados.</p>

                    <template #footer>
                        <button type="button" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#941B2B] hover:underline" @click="openRecord('conditions')">
                            <Plus class="h-4 w-4" /> Agregar antecedente
                        </button>
                    </template>
                </AdminCard>

                <RecordList
                    title="Medicamentos"
                    :icon="Pill"
                    :items="patient.medications.map((m) => ({
                        id: m.id,
                        title: m.name,
                        subtitle: [m.dose, m.frequency].filter(Boolean).join(' · ') || undefined,
                        meta: m.started_at || m.ended_at ? `${formatDate(m.started_at)} – ${m.ended_at ? formatDate(m.ended_at) : 'actual'}` : undefined,
                    }))"
                    empty-text="Sin medicamentos registrados."
                    add-label="Agregar medicamento"
                    @add="openRecord('medications')"
                    @edit="(id) => openRecord('medications', id)"
                    @remove="(id) => removeRecord('medications', id)"
                />

                <RecordList
                    title="Contactos de emergencia"
                    :icon="Phone"
                    :items="patient.emergency_contacts.map((c) => ({
                        id: c.id,
                        title: c.name,
                        subtitle: [c.relationship, c.phone].filter(Boolean).join(' · '),
                        meta: c.alternate_phone ? `Alterno: ${c.alternate_phone}` : undefined,
                    }))"
                    empty-text="Sin contactos registrados."
                    add-label="Agregar contacto"
                    @add="openRecord('contacts')"
                    @edit="(id) => openRecord('contacts', id)"
                    @remove="(id) => removeRecord('contacts', id)"
                />

                <AttachmentsCard
                    attachable-type="patient"
                    :attachable-id="patient.id"
                    :attachments="patient.attachments"
                    title="Documentos clínicos"
                    description="Consentimientos informados, radiografías, estudios y fotografías."
                />
            </div>
        </div>

        <!-- Diálogo de diagnóstico -->
        <FormDialog
            v-model:open="diagnosisDialog"
            :title="editingDiagnosis ? 'Editar diagnóstico' : 'Registrar diagnóstico'"
            :description="editingDiagnosis ? 'Al guardar cambios vuelve a quedar pendiente de revisión.' : undefined"
            :loading="diagnosisForm.processing"
            @submit="submitDiagnosis"
        >
            <div class="md:col-span-2">
                <FormSelect id="dx_catalog" v-model="diagnosisForm.diagnosis_id" label="Diagnóstico (catálogo)" placeholder="Fuera de catálogo" :options="catalogOptions" :error="diagnosisForm.errors.diagnosis_id" nullable />
            </div>
            <div class="md:col-span-2">
                <FormInput id="dx_desc" v-model="diagnosisForm.description" label="Descripción" help="Obligatoria si no eliges del catálogo." :error="diagnosisForm.errors.description" />
            </div>
            <FormSelect id="dx_type" v-model="diagnosisForm.type" label="Tipo" :options="enumOptions(diagnosisTypes)" :error="diagnosisForm.errors.type" required />
            <FormInput id="dx_date" v-model="diagnosisForm.diagnosed_at" type="date" label="Fecha" :error="diagnosisForm.errors.diagnosed_at" required />
            <FormSelect id="dx_tooth" v-model="diagnosisForm.tooth" label="Diente" placeholder="General" :options="toothOptions" :error="diagnosisForm.errors.tooth" />
            <div>
                <p class="form-label">Caras</p>
                <div class="mt-1 flex gap-1.5">
                    <button
                        v-for="surface in odontogram.surfaces"
                        :key="surface"
                        type="button"
                        :disabled="!diagnosisForm.tooth"
                        :class="[
                            'h-10 w-10 rounded-lg border text-sm font-bold transition disabled:opacity-40',
                            diagnosisForm.surfaces.includes(surface) ? 'border-[#941B2B] bg-[#941B2B] text-white' : 'border-[#E3E5E8] bg-white text-[#383D43]',
                        ]"
                        @click="toggleSurface(surface)"
                    >
                        {{ surface }}
                    </button>
                </div>
                <p v-if="diagnosisForm.errors.surfaces" class="form-error">{{ diagnosisForm.errors.surfaces }}</p>
            </div>
            <div class="md:col-span-2">
                <FormSelect id="dx_consult" v-model="diagnosisForm.patient_consultation_id" label="Consulta" placeholder="Sin consulta" :options="consultationOptions" :error="diagnosisForm.errors.patient_consultation_id" nullable />
            </div>
            <FormSelect id="dx_student" v-model="diagnosisForm.student_profile_id" label="Alumno" placeholder="Sin especificar" :options="studentOptions" :error="diagnosisForm.errors.student_profile_id" nullable />
            <FormSelect id="dx_teacher" v-model="diagnosisForm.teacher_profile_id" label="Docente que revisa" placeholder="Cualquier docente" :options="teacherOptions" :error="diagnosisForm.errors.teacher_profile_id" nullable />
            <div class="md:col-span-2">
                <FormTextarea id="dx_notes" v-model="diagnosisForm.notes" label="Notas" :rows="2" :error="diagnosisForm.errors.notes" />
            </div>
        </FormDialog>

        <FormDialog v-model:open="planDialog" title="Nuevo plan de tratamiento" submit-label="Crear plan" :loading="planForm.processing" @submit="submitPlan">
            <div class="md:col-span-2">
                <FormInput id="plan_title" v-model="planForm.title" label="Título" :error="planForm.errors.title" required />
            </div>
            <FormInput id="plan_start" v-model="planForm.started_at" type="date" label="Inicio" :error="planForm.errors.started_at" required />
            <div />
            <FormSelect id="plan_student" v-model="planForm.student_profile_id" label="Alumno responsable" placeholder="Sin especificar" :options="studentOptions" :error="planForm.errors.student_profile_id" nullable />
            <FormSelect id="plan_teacher" v-model="planForm.teacher_profile_id" label="Docente que autoriza" placeholder="Cualquier docente" :options="teacherOptions" :error="planForm.errors.teacher_profile_id" nullable />
            <div class="md:col-span-2">
                <FormTextarea id="plan_notes" v-model="planForm.notes" label="Notas" :rows="3" :error="planForm.errors.notes" />
            </div>
        </FormDialog>

        <ReviewDialog
            v-model:open="reviewOpen"
            reviewable-type="patient_diagnosis"
            :reviewable-id="reviewing?.id ?? null"
            :title="reviewing ? diagnosisTitle(reviewing) : ''"
            :decisions="['autorizado', 'correcciones', 'rechazado']"
        />

        <!-- Diálogo de registros -->
        <FormDialog
            v-model:open="dialogOpen"
            :title="`${editingId ? 'Editar' : 'Agregar'} ${TITLES[recordType]}`"
            :loading="recordForm.processing"
            @submit="submitRecord"
        >
            <template v-if="recordType === 'contacts'">
                <div class="md:col-span-2">
                    <FormInput id="c_name" v-model="recordForm.name" label="Nombre" :error="recordForm.errors.name" required />
                </div>
                <FormInput id="c_rel" v-model="recordForm.relationship" label="Parentesco" placeholder="Ej. Madre" :error="recordForm.errors.relationship" />
                <FormInput id="c_phone" v-model="recordForm.phone" type="tel" label="Teléfono" :error="recordForm.errors.phone" required />
                <FormInput id="c_alt" v-model="recordForm.alternate_phone" type="tel" label="Teléfono alterno" :error="recordForm.errors.alternate_phone" />
            </template>

            <template v-else-if="recordType === 'conditions'">
                <FormSelect id="cond_cat" v-model="recordForm.category" label="Tipo" :options="enumOptions(conditionCategories)" :error="recordForm.errors.category" required />
                <FormInput id="cond_name" v-model="recordForm.name" label="Antecedente" placeholder="Ej. Diabetes tipo 2" :error="recordForm.errors.name" required />
                <div class="md:col-span-2">
                    <FormTextarea id="cond_notes" v-model="recordForm.notes" label="Detalles" :rows="2" :error="recordForm.errors.notes" />
                </div>
            </template>

            <template v-else-if="recordType === 'allergies'">
                <FormInput id="al_allergen" v-model="recordForm.allergen" label="Alérgeno" placeholder="Ej. Penicilina, látex" :error="recordForm.errors.allergen" required />
                <FormSelect id="al_sev" v-model="recordForm.severity" label="Gravedad" :options="enumOptions(allergySeverities)" :error="recordForm.errors.severity" required />
                <div class="md:col-span-2">
                    <FormInput id="al_reaction" v-model="recordForm.reaction" label="Reacción" :error="recordForm.errors.reaction" />
                </div>
            </template>

            <template v-else-if="recordType === 'medications'">
                <FormInput id="m_name" v-model="recordForm.name" label="Medicamento" :error="recordForm.errors.name" required />
                <FormInput id="m_dose" v-model="recordForm.dose" label="Dosis" placeholder="Ej. 500 mg" :error="recordForm.errors.dose" />
                <FormInput id="m_freq" v-model="recordForm.frequency" label="Frecuencia" placeholder="Ej. Cada 8 horas" :error="recordForm.errors.frequency" />
                <div />
                <FormInput id="m_start" v-model="recordForm.started_at" type="date" label="Desde" :error="recordForm.errors.started_at" />
                <FormInput id="m_end" v-model="recordForm.ended_at" type="date" label="Hasta" help="Vacío si lo sigue tomando." :error="recordForm.errors.ended_at" />
                <div class="md:col-span-2">
                    <FormTextarea id="m_notes" v-model="recordForm.notes" label="Notas" :rows="2" :error="recordForm.errors.notes" />
                </div>
            </template>

            <template v-else-if="recordType === 'consultations'">
                <FormInput id="co_at" v-model="recordForm.attended_at" type="datetime-local" label="Fecha y hora" :error="recordForm.errors.attended_at" required />
                <FormSelect id="co_clinic" v-model="recordForm.clinic_id" label="Clínica" placeholder="Sin especificar" :options="recordOptions(clinics)" :error="recordForm.errors.clinic_id" nullable />
                <FormSelect id="co_student" v-model="recordForm.student_profile_id" label="Alumno que atiende" placeholder="Sin especificar" :options="studentOptions" :error="recordForm.errors.student_profile_id" nullable />
                <FormSelect id="co_teacher" v-model="recordForm.teacher_profile_id" label="Docente supervisor" placeholder="Sin especificar" :options="teacherOptions" :error="recordForm.errors.teacher_profile_id" nullable />
                <div class="md:col-span-2">
                    <FormSelect id="co_session" v-model="recordForm.clinical_session_id" label="Sesión clínica" placeholder="Fuera de una sesión" :options="sessionOptions" :error="recordForm.errors.clinical_session_id" nullable />
                </div>
                <div class="md:col-span-2">
                    <FormTextarea id="co_reason" v-model="recordForm.reason" label="Motivo de consulta" :rows="2" :error="recordForm.errors.reason" required />
                </div>

                <div class="md:col-span-2">
                    <p class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-[#858B93]">
                        <Activity class="h-3.5 w-3.5" /> Signos vitales
                    </p>
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                        <FormInput id="co_bp" v-model="recordForm.blood_pressure" label="Tensión arterial" placeholder="120/80" :error="recordForm.errors.blood_pressure" />
                        <FormInput id="co_hr" v-model="recordForm.heart_rate" type="number" label="Frec. cardiaca" :error="recordForm.errors.heart_rate" />
                        <FormInput id="co_rr" v-model="recordForm.respiratory_rate" type="number" label="Frec. respiratoria" :error="recordForm.errors.respiratory_rate" />
                        <FormInput id="co_temp" v-model="recordForm.temperature" type="number" step="0.1" label="Temperatura °C" :error="recordForm.errors.temperature" />
                        <FormInput id="co_weight" v-model="recordForm.weight" type="number" step="0.01" label="Peso kg" :error="recordForm.errors.weight" />
                        <FormInput id="co_height" v-model="recordForm.height" type="number" step="0.01" label="Talla m" :error="recordForm.errors.height" />
                    </div>
                </div>

                <div class="md:col-span-2">
                    <FormTextarea id="co_findings" v-model="recordForm.findings" label="Exploración" :rows="3" :error="recordForm.errors.findings" />
                </div>
                <div class="md:col-span-2">
                    <FormTextarea id="co_notes" v-model="recordForm.notes" label="Observaciones" :rows="2" :error="recordForm.errors.notes" />
                </div>
            </template>

            <template v-else>
                <FormInput id="n_at" v-model="recordForm.noted_at" type="datetime-local" label="Fecha y hora" :error="recordForm.errors.noted_at" required />
                <FormSelect id="n_consult" v-model="recordForm.patient_consultation_id" label="Consulta" placeholder="Sin consulta" :options="consultationOptions" :error="recordForm.errors.patient_consultation_id" nullable />
                <FormSelect id="n_student" v-model="recordForm.student_profile_id" label="Alumno" placeholder="Sin especificar" :options="studentOptions" :error="recordForm.errors.student_profile_id" nullable />
                <FormSelect id="n_teacher" v-model="recordForm.teacher_profile_id" label="Docente supervisor" placeholder="Sin especificar" :options="teacherOptions" :error="recordForm.errors.teacher_profile_id" nullable />
                <div class="md:col-span-2">
                    <FormTextarea id="n_note" v-model="recordForm.note" label="Nota" :rows="5" :error="recordForm.errors.note" required />
                </div>
            </template>
        </FormDialog>
    </div>
</template>
