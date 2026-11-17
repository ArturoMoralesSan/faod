<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ClipboardCheck, ClipboardPlus, Layers, Package, Pencil, Plus, Receipt, Trash2, TriangleAlert } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import AdminButton from '@/components/admin/AdminButton.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import DetailItem from '@/components/admin/DetailItem.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import ReviewDialog from '@/components/admin/ReviewDialog.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import {
    confirmDelete,
    enumOptions,
    formatDate,
    formatDateTime,
    formatMoney,
    formatQty,
    fullName,
    label,
    toDateInput,
    toDateTimeInput,
} from '@/lib/admin';
import admin from '@/routes/admin';
import type { PersonName, ReviewData, SelectOption, StudentOption, TeacherOption } from '@/types/admin';

type Person = { people: PersonName | null } | null;

interface TreatmentProcedure {
    id: number;
    treatment_stage_id: number | null;
    procedure_id: number | null;
    patient_diagnosis_id: number | null;
    description: string | null;
    tooth: string | null;
    surfaces: string | null;
    status: string;
    review_status: string | null;
    cost: number | null;
    performed_at: string | null;
    student_profile_id: number | null;
    teacher_profile_id: number | null;
    notes: string | null;
    procedure: { id: number; code: string; name: string } | null;
    diagnosis: { id: number; tooth: string | null; description: string | null; diagnosis: { code: string; name: string } | null } | null;
    student: Person;
    teacher: Person;
    materials: { id: number; name: string; quantity: number; unit: string | null }[];
    reviews: ReviewData[];
}

const props = defineProps<{
    plan: {
        id: number;
        patient_id: number;
        title: string;
        notes: string | null;
        status: string;
        review_status: string;
        started_at: string;
        completed_at: string | null;
        student_profile_id: number | null;
        teacher_profile_id: number | null;
        patient: { id: number; record_number: string; people: PersonName; allergies: { id: number; allergen: string; severity: string }[] };
        student: Person;
        teacher: Person;
        creator: { name: string } | null;
        reviews: ReviewData[];
        stages: { id: number; order: number; name: string; description: string | null }[];
        procedures: TreatmentProcedure[];
    };
    statuses: string[];
    procedureStatuses: string[];
    decisions: string[];
    catalog: { id: number; code: string; name: string; category: string | null; default_cost: number | null }[];
    diagnoses: { id: number; tooth: string | null; surfaces: string | null; description: string | null; review_status: string; diagnosis: { code: string; name: string } | null }[];
    teeth: string[];
    inventoryItems: { id: number; code: string; name: string; unit: string; stock: number | string | null }[];
    warehouses: { id: number; code: string; name: string; clinic_id: number | null }[];
    students: StudentOption[];
    teachers: TeacherOption[];
    can: { edit: boolean; delete: boolean; review: boolean; charge: boolean };
    uncharged: number;
}>();

const chargePlan = (): void => {
    router.post(admin.finance.treatmentPlans.charges(props.plan.id).url, {}, { preserveScroll: true });
};

const studentOptions = computed<SelectOption[]>(() =>
    props.students.map((s) => ({ value: s.id, label: `${s.registration_number} · ${fullName(s.people)}` })),
);
const teacherOptions = computed<SelectOption[]>(() => props.teachers.map((t) => ({ value: t.id, label: fullName(t.people) })));
const catalogOptions = computed<SelectOption[]>(() => props.catalog.map((p) => ({ value: p.id, label: `${p.code} · ${p.name}` })));
const stageOptions = computed<SelectOption[]>(() => props.plan.stages.map((s) => ({ value: s.id, label: `${s.order}. ${s.name}` })));
const diagnosisOptions = computed<SelectOption[]>(() =>
    props.diagnoses.map((d) => ({
        value: d.id,
        label: [d.tooth ? `Diente ${d.tooth}` : null, d.diagnosis ? `${d.diagnosis.code} ${d.diagnosis.name}` : d.description].filter(Boolean).join(' · '),
    })),
);
const toothOptions = computed<SelectOption[]>(() => props.teeth.map((t) => ({ value: t, label: t })));

const procedureTitle = (p: TreatmentProcedure) =>
    [p.tooth ? `Diente ${p.tooth}${p.surfaces ? ` (${p.surfaces})` : ''}` : null, p.procedure ? `${p.procedure.code} · ${p.procedure.name}` : p.description]
        .filter(Boolean)
        .join(' · ');

/** Etapas con sus procedimientos; los que no tienen etapa al final. */
const groups = computed(() => {
    const result = props.plan.stages.map((stage) => ({
        stage,
        procedures: props.plan.procedures.filter((p) => p.treatment_stage_id === stage.id),
    }));
    const loose = props.plan.procedures.filter((p) => !p.treatment_stage_id);

    return loose.length || !result.length ? [...result, { stage: null, procedures: loose }] : result;
});

const totals = computed(() => {
    const active = props.plan.procedures.filter((p) => p.status !== 'cancelado');

    return {
        count: active.length,
        done: active.filter((p) => p.status === 'realizado').length,
        cost: active.reduce((sum, p) => sum + Number(p.cost ?? 0), 0),
        doneCost: active.filter((p) => p.status === 'realizado').reduce((sum, p) => sum + Number(p.cost ?? 0), 0),
    };
});

/* ---------------------------------- Plan ---------------------------------- */

const planDialog = ref(false);
const planForm = useForm({
    title: props.plan.title,
    notes: props.plan.notes ?? '',
    status: props.plan.status,
    started_at: toDateInput(props.plan.started_at),
    completed_at: toDateInput(props.plan.completed_at),
    student_profile_id: props.plan.student_profile_id,
    teacher_profile_id: props.plan.teacher_profile_id,
});

const submitPlan = (): void => {
    planForm.transform((data) => ({ ...data, notes: data.notes || null, completed_at: data.completed_at || null }))
        .put(admin.treatmentPlans.update(props.plan.id).url, { preserveScroll: true, onSuccess: () => (planDialog.value = false) });
};

const destroyPlan = (): void => {
    confirmDelete('¿Eliminar plan de tratamiento?', 'Se eliminará con sus etapas y procedimientos.', admin.treatmentPlans.destroy(props.plan.id).url);
};

/* --------------------------------- Etapas --------------------------------- */

const stageDialog = ref(false);
const editingStage = ref<number | null>(null);
const stageForm = useForm({ name: '', description: '', order: 1 });

const openStage = (id: number | null = null): void => {
    editingStage.value = id;
    stageForm.reset();
    stageForm.clearErrors();
    const stage = id ? props.plan.stages.find((s) => s.id === id) : null;

    if (stage) {
        stageForm.name = stage.name;
        stageForm.description = stage.description ?? '';
        stageForm.order = stage.order;
    }

    stageDialog.value = true;
};

const submitStage = (): void => {
    const options = { preserveScroll: true, onSuccess: () => (stageDialog.value = false) };

    stageForm.transform((data) => ({ ...data, description: data.description || null }));

    if (editingStage.value) {
        stageForm.put(admin.treatmentStages.update(editingStage.value).url, options);
    } else {
        stageForm.transform((data) => ({ name: data.name, description: data.description || null }))
            .post(admin.treatmentPlans.stages.store(props.plan.id).url, options);
    }
};

const removeStage = (id: number): void => {
    confirmDelete('¿Eliminar etapa?', 'Sus procedimientos se conservan sin etapa.', admin.treatmentStages.destroy(id).url, { preserveState: true });
};

/* ----------------------------- Procedimientos ----------------------------- */

const procedureDialog = ref(false);
const editingProcedure = ref<number | null>(null);

const emptyProcedure = {
    treatment_stage_id: null as number | null,
    procedure_id: null as number | null,
    description: '',
    patient_diagnosis_id: null as number | null,
    tooth: '',
    surfaces: [] as string[],
    status: 'planeado',
    cost: null as number | null,
    performed_at: '',
    student_profile_id: null as number | null,
    teacher_profile_id: null as number | null,
    notes: '',
};

const procedureForm = useForm({ ...emptyProcedure });

const openProcedure = (id: number | null = null, stageId: number | null = null): void => {
    editingProcedure.value = id;
    procedureForm.defaults({ ...emptyProcedure, surfaces: [] });
    procedureForm.reset();
    procedureForm.clearErrors();

    const p = id ? props.plan.procedures.find((item) => item.id === id) : null;

    if (p) {
        Object.assign(procedureForm, {
            treatment_stage_id: p.treatment_stage_id,
            procedure_id: p.procedure_id,
            description: p.description ?? '',
            patient_diagnosis_id: p.patient_diagnosis_id,
            tooth: p.tooth ?? '',
            surfaces: p.surfaces ? p.surfaces.split('') : [],
            status: p.status,
            cost: p.cost,
            performed_at: toDateTimeInput(p.performed_at),
            student_profile_id: p.student_profile_id,
            teacher_profile_id: p.teacher_profile_id,
            notes: p.notes ?? '',
        });
    } else {
        procedureForm.treatment_stage_id = stageId;
        procedureForm.student_profile_id = props.plan.student_profile_id;
        procedureForm.teacher_profile_id = props.plan.teacher_profile_id;
    }

    procedureDialog.value = true;
};

// Al elegir del catálogo se propone su costo.
const onCatalogChange = (): void => {
    const item = props.catalog.find((c) => c.id === procedureForm.procedure_id);

    if (item && (procedureForm.cost === null || procedureForm.cost === undefined || String(procedureForm.cost) === '')) {
        procedureForm.cost = item.default_cost;
    }
};

// Al elegir un diagnóstico con diente se copia el diente y sus caras.
const onDiagnosisChange = (): void => {
    const d = props.diagnoses.find((item) => item.id === procedureForm.patient_diagnosis_id);

    if (d?.tooth && !procedureForm.tooth) {
        procedureForm.tooth = d.tooth;
        procedureForm.surfaces = d.surfaces ? d.surfaces.split('') : [];
    }
};

const toggleSurface = (surface: string): void => {
    const list = procedureForm.surfaces;
    const index = list.indexOf(surface);
    index === -1 ? list.push(surface) : list.splice(index, 1);
};

const submitProcedure = (): void => {
    const options = { preserveScroll: true, onSuccess: () => (procedureDialog.value = false) };

    procedureForm.transform((data) => ({
        ...data,
        description: data.description || null,
        tooth: data.tooth || null,
        surfaces: data.surfaces.join('') || null,
        cost: data.cost === null || String(data.cost) === '' ? null : data.cost,
        performed_at: data.performed_at || null,
        notes: data.notes || null,
    }));

    if (editingProcedure.value) {
        procedureForm.put(admin.treatmentProcedures.update(editingProcedure.value).url, options);
    } else {
        procedureForm.post(admin.treatmentPlans.procedures.store(props.plan.id).url, options);
    }
};

const removeProcedure = (id: number): void => {
    confirmDelete('¿Eliminar procedimiento?', 'Se eliminará del plan con sus materiales.', admin.treatmentProcedures.destroy(id).url, { preserveState: true });
};

/* -------------------------------- Materiales -------------------------------- */

const materialDialog = ref(false);
const materialProcedure = ref<TreatmentProcedure | null>(null);
const materialForm = useForm({
    inventory_item_id: null as number | null,
    warehouse_id: null as number | null,
    name: '',
    quantity: 1 as number | null,
    unit: 'pza',
});

const inventoryOptions = computed<SelectOption[]>(() =>
    props.inventoryItems.map((i) => ({ value: i.id, label: `${i.code} · ${i.name} (${formatQty(i.stock)} ${i.unit})` })),
);
const warehouseOptions = computed<SelectOption[]>(() => props.warehouses.map((w) => ({ value: w.id, label: w.name })));
const selectedItem = computed(() => props.inventoryItems.find((i) => i.id === materialForm.inventory_item_id));

const openMaterial = (p: TreatmentProcedure): void => {
    materialProcedure.value = p;
    materialForm.reset();
    materialForm.clearErrors();
    materialForm.warehouse_id = props.warehouses.length === 1 ? props.warehouses[0].id : null;
    materialDialog.value = true;
};

const submitMaterial = (): void => {
    if (!materialProcedure.value) {
        return;
    }

    materialForm.transform((data) => (data.inventory_item_id
        ? { inventory_item_id: data.inventory_item_id, warehouse_id: data.warehouse_id, quantity: data.quantity }
        : { name: data.name, quantity: data.quantity, unit: data.unit || null }));

    materialForm.post(admin.treatmentProcedures.materials.store(materialProcedure.value.id).url, {
        preserveScroll: true,
        onSuccess: () => (materialDialog.value = false),
    });
};

const removeMaterial = (id: number): void => {
    confirmDelete('¿Eliminar material?', 'Se quitará del procedimiento.', admin.procedureMaterials.destroy(id).url, { preserveState: true });
};

/* --------------------------------- Revisión --------------------------------- */

const reviewOpen = ref(false);
const review = ref<{ type: string; id: number; title: string } | null>(null);

const openReview = (type: string, id: number, title: string): void => {
    review.value = { type, id, title };
    reviewOpen.value = true;
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Pacientes', href: admin.patients.index() },
            { title: 'Plan de tratamiento', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="plan.title" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            :title="plan.title"
            :description="`${plan.patient.record_number} · ${fullName(plan.patient.people)}`"
            section="Plan de tratamiento"
            :icon="ClipboardPlus"
            :action-icon="ArrowLeft"
            action-label="Volver al expediente"
            :action-href="admin.patients.show(plan.patient_id).url"
        >
            <template #details>
                <div class="mt-2 flex gap-2">
                    <StatusBadge :value="plan.status" />
                    <StatusBadge :value="plan.review_status" />
                </div>
            </template>
        </PageHeader>

        <div v-if="plan.patient.allergies.length" class="flex items-start gap-3 rounded-2xl border border-[#E8C7CC] bg-[#F9ECEE] p-4 text-[#941B2B]">
            <TriangleAlert class="mt-0.5 h-5 w-5 shrink-0" />
            <p class="text-sm"><span class="font-bold">Alergias:</span> {{ plan.patient.allergies.map((a) => a.allergen).join(', ') }}</p>
        </div>

        <div class="grid gap-5 xl:grid-cols-3">
            <AdminCard title="Resumen del plan" :icon="ClipboardPlus" class="xl:col-span-2">
                <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <DetailItem label="Inicio" :value="formatDate(plan.started_at)" />
                    <DetailItem label="Conclusión" :value="plan.completed_at ? formatDate(plan.completed_at) : null" />
                    <DetailItem label="Alumno" :value="plan.student ? fullName(plan.student.people) : null" />
                    <DetailItem label="Docente" :value="plan.teacher ? fullName(plan.teacher.people) : null" />
                    <DetailItem label="Avance" :value="`${totals.done} de ${totals.count} procedimientos`" />
                    <DetailItem label="Costo estimado" :value="formatMoney(totals.cost)" />
                    <DetailItem label="Realizado" :value="formatMoney(totals.doneCost)" />
                    <DetailItem label="Registró" :value="plan.creator?.name" />
                </dl>
                <p v-if="plan.notes" class="mt-5 whitespace-pre-line text-sm leading-6 text-[#525960]">{{ plan.notes }}</p>

                <template #footer>
                    <div class="flex flex-wrap gap-2">
                        <AdminButton v-if="can.edit" variant="secondary" :icon="Pencil" @click="planDialog = true">Editar plan</AdminButton>
                        <AdminButton v-if="can.review && plan.review_status !== 'autorizado'" variant="secondary" :icon="ClipboardCheck" @click="openReview('treatment_plan', plan.id, plan.title)">
                            Revisar plan
                        </AdminButton>
                        <AdminButton v-if="can.charge && uncharged" variant="secondary" :icon="Receipt" @click="chargePlan">
                            Cobrar {{ uncharged }} procedimiento(s) realizado(s)
                        </AdminButton>
                        <Link v-if="can.charge" :href="admin.finance.accounts.show({ type: 'patient', id: plan.patient_id }).url" class="inline-flex items-center px-2 text-sm font-semibold text-[#941B2B] hover:underline">
                            Estado de cuenta
                        </Link>
                        <AdminButton v-if="can.delete" variant="secondary" :icon="Trash2" @click="destroyPlan">Eliminar</AdminButton>
                    </div>
                </template>
            </AdminCard>

            <AdminCard title="Revisiones del plan" :icon="ClipboardCheck">
                <ul v-if="plan.reviews.length" class="flex flex-col gap-3">
                    <li v-for="r in plan.reviews" :key="r.id" class="text-sm">
                        <div class="flex items-center gap-2">
                            <StatusBadge :value="r.decision" />
                            <span class="text-xs text-[#858B93]">{{ formatDateTime(r.created_at) }}<template v-if="r.reviewer"> · {{ r.reviewer.name }}</template></span>
                        </div>
                        <p v-if="r.comments" class="mt-1 whitespace-pre-line text-[#525960]">{{ r.comments }}</p>
                    </li>
                </ul>
                <p v-else class="text-sm italic text-[#858B93]">Aún no se ha revisado.</p>
            </AdminCard>
        </div>

        <!-- Etapas y procedimientos -->
        <AdminCard v-for="group in groups" :key="group.stage?.id ?? 'sin-etapa'" :title="group.stage ? `${group.stage.order}. ${group.stage.name}` : 'Procedimientos sin etapa'" :description="group.stage?.description ?? undefined" :icon="Layers">
            <ul v-if="group.procedures.length" class="flex flex-col gap-3">
                <li v-for="p in group.procedures" :key="p.id" class="rounded-xl border border-[#E3E5E8] p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p :class="['text-sm font-semibold', p.status === 'cancelado' ? 'text-[#858B93] line-through' : 'text-[#292D32]']">{{ procedureTitle(p) }}</p>
                                <StatusBadge :value="p.status" />
                                <StatusBadge v-if="p.review_status" :value="p.review_status" />
                            </div>
                            <p class="mt-1 text-xs text-[#858B93]">
                                {{ [p.cost !== null ? formatMoney(p.cost) : null, p.performed_at ? `Realizado: ${formatDateTime(p.performed_at)}` : null, p.student ? fullName(p.student.people) : null, p.teacher ? `Valida: ${fullName(p.teacher.people)}` : null].filter(Boolean).join(' · ') }}
                            </p>
                            <p v-if="p.diagnosis" class="text-xs text-[#525960]">Diagnóstico: {{ p.diagnosis.diagnosis?.name ?? p.diagnosis.description }}</p>
                            <p v-if="p.notes" class="mt-1 whitespace-pre-line text-xs text-[#525960]">{{ p.notes }}</p>
                            <p v-if="p.reviews[0]?.comments" class="mt-2 rounded-lg bg-[#F7F8FA] px-3 py-2 text-xs text-[#525960]">
                                <span class="font-semibold">{{ label(p.reviews[0].decision) }}:</span> {{ p.reviews[0].comments }}
                            </p>

                            <div v-if="p.materials.length" class="mt-2 flex flex-wrap gap-1.5">
                                <span v-for="m in p.materials" :key="m.id" class="inline-flex items-center gap-1 rounded-full border border-[#E3E5E8] bg-white px-2 py-0.5 text-xs text-[#525960]">
                                    {{ m.name }} · {{ formatQty(m.quantity) }} {{ m.unit }}
                                    <button v-if="can.edit" type="button" class="text-[#941B2B]" title="Quitar" @click="removeMaterial(m.id)">×</button>
                                </span>
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-wrap gap-1">
                            <button
                                v-if="can.review && p.review_status && p.review_status !== 'autorizado'"
                                type="button"
                                class="inline-flex h-8 items-center gap-1 rounded-lg border border-[#BFE3CC] px-2 text-xs font-semibold text-[#187744] hover:bg-[#E2F3E8]"
                                @click="openReview('treatment_procedure', p.id, procedureTitle(p))"
                            >
                                <ClipboardCheck class="h-4 w-4" /> Validar
                            </button>
                            <template v-if="can.edit">
                                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#383D43] hover:bg-[#F9ECEE]" title="Agregar material" @click="openMaterial(p)">
                                    <Package class="h-4 w-4" />
                                </button>
                                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#383D43] hover:bg-[#F9ECEE]" title="Editar" @click="openProcedure(p.id)">
                                    <Pencil class="h-4 w-4" />
                                </button>
                                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F9ECEE]" title="Eliminar" @click="removeProcedure(p.id)">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </template>
                        </div>
                    </div>
                </li>
            </ul>
            <p v-else class="text-sm italic text-[#858B93]">Sin procedimientos.</p>

            <template v-if="can.edit" #footer>
                <div class="flex flex-wrap items-center gap-4">
                    <button type="button" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#941B2B] hover:underline" @click="openProcedure(null, group.stage?.id ?? null)">
                        <Plus class="h-4 w-4" /> Agregar procedimiento
                    </button>
                    <template v-if="group.stage">
                        <button type="button" class="text-sm font-semibold text-[#525960] hover:underline" @click="openStage(group.stage.id)">Editar etapa</button>
                        <button type="button" class="text-sm font-semibold text-[#525960] hover:underline" @click="removeStage(group.stage.id)">Eliminar etapa</button>
                    </template>
                </div>
            </template>
        </AdminCard>

        <div v-if="can.edit">
            <AdminButton variant="secondary" :icon="Layers" @click="openStage()">Agregar etapa</AdminButton>
        </div>

        <!-- Diálogos -->
        <FormDialog v-model:open="planDialog" title="Editar plan" description="Cambiar título, notas o docente vuelve a requerir autorización." :loading="planForm.processing" @submit="submitPlan">
            <div class="md:col-span-2">
                <FormInput id="p_title" v-model="planForm.title" label="Título" :error="planForm.errors.title" required />
            </div>
            <FormSelect id="p_status" v-model="planForm.status" label="Estado" :options="enumOptions(statuses)" :error="planForm.errors.status" required />
            <div />
            <FormInput id="p_start" v-model="planForm.started_at" type="date" label="Inicio" :error="planForm.errors.started_at" required />
            <FormInput id="p_end" v-model="planForm.completed_at" type="date" label="Conclusión" :error="planForm.errors.completed_at" />
            <FormSelect id="p_student" v-model="planForm.student_profile_id" label="Alumno" placeholder="Sin especificar" :options="studentOptions" nullable />
            <FormSelect id="p_teacher" v-model="planForm.teacher_profile_id" label="Docente que autoriza" placeholder="Cualquier docente" :options="teacherOptions" nullable />
            <div class="md:col-span-2">
                <FormTextarea id="p_notes" v-model="planForm.notes" label="Notas" :rows="3" :error="planForm.errors.notes" />
            </div>
        </FormDialog>

        <FormDialog v-model:open="stageDialog" :title="editingStage ? 'Editar etapa' : 'Agregar etapa'" :loading="stageForm.processing" @submit="submitStage">
            <div class="md:col-span-2">
                <FormInput id="s_name" v-model="stageForm.name" label="Nombre" placeholder="Ej. Fase higiénica" :error="stageForm.errors.name" required />
            </div>
            <FormInput v-if="editingStage" id="s_order" v-model="stageForm.order" type="number" min="1" label="Orden" :error="stageForm.errors.order" />
            <div class="md:col-span-2">
                <FormTextarea id="s_desc" v-model="stageForm.description" label="Descripción" :rows="2" :error="stageForm.errors.description" />
            </div>
        </FormDialog>

        <FormDialog
            v-model:open="procedureDialog"
            :title="editingProcedure ? 'Editar procedimiento' : 'Agregar procedimiento'"
            description="Al marcarlo como realizado queda pendiente de validación docente."
            :loading="procedureForm.processing"
            @submit="submitProcedure"
        >
            <div class="md:col-span-2" @change="onCatalogChange">
                <FormSelect id="pr_catalog" v-model="procedureForm.procedure_id" label="Procedimiento (catálogo)" placeholder="Fuera de catálogo" :options="catalogOptions" :error="procedureForm.errors.procedure_id" nullable />
            </div>
            <div class="md:col-span-2">
                <FormInput id="pr_desc" v-model="procedureForm.description" label="Descripción" help="Obligatoria si no eliges del catálogo." :error="procedureForm.errors.description" />
            </div>
            <div class="md:col-span-2" @change="onDiagnosisChange">
                <FormSelect id="pr_dx" v-model="procedureForm.patient_diagnosis_id" label="Diagnóstico que atiende" placeholder="Ninguno" :options="diagnosisOptions" :error="procedureForm.errors.patient_diagnosis_id" nullable />
            </div>
            <FormSelect id="pr_tooth" v-model="procedureForm.tooth" label="Diente" placeholder="General" :options="toothOptions" :error="procedureForm.errors.tooth" />
            <div>
                <p class="form-label">Caras</p>
                <div class="mt-1 flex gap-1.5">
                    <button
                        v-for="surface in ['M', 'O', 'D', 'V', 'L']"
                        :key="surface"
                        type="button"
                        :disabled="!procedureForm.tooth"
                        :class="[
                            'h-10 w-10 rounded-lg border text-sm font-bold transition disabled:opacity-40',
                            procedureForm.surfaces.includes(surface) ? 'border-[#941B2B] bg-[#941B2B] text-white' : 'border-[#E3E5E8] bg-white text-[#383D43]',
                        ]"
                        @click="toggleSurface(surface)"
                    >
                        {{ surface }}
                    </button>
                </div>
            </div>
            <FormSelect id="pr_stage" v-model="procedureForm.treatment_stage_id" label="Etapa" placeholder="Sin etapa" :options="stageOptions" :error="procedureForm.errors.treatment_stage_id" nullable />
            <FormSelect id="pr_status" v-model="procedureForm.status" label="Estado" :options="enumOptions(procedureStatuses)" :error="procedureForm.errors.status" required />
            <FormInput id="pr_cost" v-model="procedureForm.cost" type="number" step="0.01" min="0" label="Costo" :error="procedureForm.errors.cost" />
            <FormInput
                v-if="procedureForm.status === 'realizado'"
                id="pr_done"
                v-model="procedureForm.performed_at"
                type="datetime-local"
                label="Fecha de realización"
                help="Vacío = ahora."
                :error="procedureForm.errors.performed_at"
            />
            <div v-else />
            <FormSelect id="pr_student" v-model="procedureForm.student_profile_id" label="Alumno" placeholder="Sin especificar" :options="studentOptions" nullable />
            <FormSelect id="pr_teacher" v-model="procedureForm.teacher_profile_id" label="Docente que valida" placeholder="Cualquier docente" :options="teacherOptions" nullable />
            <div class="md:col-span-2">
                <FormTextarea id="pr_notes" v-model="procedureForm.notes" label="Notas" :rows="2" :error="procedureForm.errors.notes" />
            </div>
        </FormDialog>

        <FormDialog v-model:open="materialDialog" :title="`Material utilizado${materialProcedure ? ` · ${procedureTitle(materialProcedure)}` : ''}`" :loading="materialForm.processing" @submit="submitMaterial">
            <div class="md:col-span-2">
                <FormSelect
                    id="m_item"
                    v-model="materialForm.inventory_item_id"
                    label="Artículo del inventario"
                    placeholder="Material no inventariado"
                    :options="inventoryOptions"
                    help="Si eliges un artículo, se descuenta de la existencia."
                    :error="materialForm.errors.inventory_item_id"
                    nullable
                />
            </div>
            <template v-if="materialForm.inventory_item_id">
                <FormSelect id="m_wh" v-model="materialForm.warehouse_id" label="Almacén" :options="warehouseOptions" :error="materialForm.errors.warehouse_id" required />
                <FormInput id="m_qty_inv" v-model="materialForm.quantity" type="number" step="0.01" min="0.01" :label="`Cantidad (${selectedItem?.unit ?? ''})`" :error="materialForm.errors.quantity" required />
            </template>
            <template v-else>
                <div class="md:col-span-2">
                    <FormInput id="m_name" v-model="materialForm.name" label="Material" placeholder="Ej. Resina A2" :error="materialForm.errors.name" required />
                </div>
                <FormInput id="m_qty" v-model="materialForm.quantity" type="number" step="0.01" min="0.01" label="Cantidad" :error="materialForm.errors.quantity" required />
                <FormInput id="m_unit" v-model="materialForm.unit" label="Unidad" placeholder="pza, ml, g" :error="materialForm.errors.unit" />
            </template>
        </FormDialog>

        <ReviewDialog
            v-model:open="reviewOpen"
            :reviewable-type="review?.type ?? 'treatment_plan'"
            :reviewable-id="review?.id ?? null"
            :title="review?.title ?? ''"
            :decisions="decisions"
        />
    </div>
</template>
