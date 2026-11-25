<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { BookOpen, Layers, Library, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import AdminButton from '@/components/admin/AdminButton.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import AttachmentsCard from '@/components/admin/AttachmentsCard.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { confirmDelete, formatDate } from '@/lib/admin';
import { can as hasPermission } from '@/lib/permissions';
import admin from '@/routes/admin';
import type { Attachment, Option } from '@/types/admin';

interface Module {
    id: number;
    name: string;
    period_number: number | null;
    description: string | null;
    sort: number;
}

interface PlanSubject {
    id: number;
    code: string;
    name: string;
    credits: string | null;
    theory_hours: number;
    practice_hours: number;
    pivot: {
        academic_module_id: number | null;
        period_number: number;
        is_required: boolean;
        sort: number;
    };
}

/** Sin permiso de edición la página es de solo consulta. */
const canEdit = hasPermission('study-plans.edit');

const props = defineProps<{
    plan: {
        id: number;
        name: string;
        version: string;
        valid_from: string | null;
        description: string | null;
        is_active: boolean;
        academic_program: Option & { total_periods: number | null };
        modules: Module[];
        subjects: PlanSubject[];
        attachments: Attachment[];
    };
    subjectsByPeriod: Record<string, PlanSubject[]>;
    availableSubjects: Option[];
}>();

const moduleName = (id: number | null) => props.plan.modules.find((m) => m.id === id)?.name;

const moduleOptions = computed(() =>
    props.plan.modules.map((module) => ({ value: module.id, label: module.name })),
);

const totalCredits = (subjects: PlanSubject[]) =>
    subjects.reduce((sum, subject) => sum + Number(subject.credits ?? 0), 0);

/* ----------------------------- Módulos ----------------------------- */

const moduleDialog = ref(false);
const editingModule = ref<Module | null>(null);
const moduleForm = useForm({
    name: '',
    period_number: null as number | null,
    description: '',
    sort: 0 as number | null,
});

const openModule = (module: Module | null = null): void => {
    editingModule.value = module;
    moduleForm.clearErrors();
    moduleForm.name = module?.name ?? '';
    moduleForm.period_number = module?.period_number ?? null;
    moduleForm.description = module?.description ?? '';
    moduleForm.sort = module?.sort ?? props.plan.modules.length;
    moduleDialog.value = true;
};

const submitModule = (): void => {
    const options = { preserveScroll: true, onSuccess: () => (moduleDialog.value = false) };

    if (editingModule.value) {
        moduleForm.put(admin.academicModules.update(editingModule.value.id).url, options);
    } else {
        moduleForm.post(admin.studyPlans.modules.store(props.plan.id).url, options);
    }
};

const destroyModule = (module: Module): void => {
    confirmDelete(
        '¿Eliminar módulo?',
        `Se eliminará "${module.name}". Las asignaturas quedarán sin módulo.`,
        admin.academicModules.destroy(module.id).url,
        { preserveState: true },
    );
};

/* --------------------------- Asignaturas --------------------------- */

const subjectDialog = ref(false);
const editingSubject = ref<PlanSubject | null>(null);
const subjectForm = useForm({
    subject_id: null as number | null,
    academic_module_id: null as number | null,
    period_number: 1 as number | null,
    is_required: true,
    sort: 0 as number | null,
});

const openSubject = (subject: PlanSubject | null = null, period = 1): void => {
    editingSubject.value = subject;
    subjectForm.clearErrors();
    subjectForm.subject_id = subject?.id ?? null;
    subjectForm.academic_module_id = subject?.pivot.academic_module_id ?? null;
    subjectForm.period_number = subject?.pivot.period_number ?? period;
    subjectForm.is_required = subject?.pivot.is_required ?? true;
    subjectForm.sort = subject?.pivot.sort ?? 0;
    subjectDialog.value = true;
};

const submitSubject = (): void => {
    const options = { preserveScroll: true, onSuccess: () => (subjectDialog.value = false) };

    if (editingSubject.value) {
        subjectForm
            .transform(({ subject_id: _ignored, ...data }) => data)
            .put(
                admin.studyPlans.subjects.update({ study_plan: props.plan.id, subject: editingSubject.value.id }).url,
                options,
            );
    } else {
        subjectForm
            .transform((data) => data)
            .post(admin.studyPlans.subjects.store(props.plan.id).url, options);
    }
};

const removeSubject = (subject: PlanSubject): void => {
    confirmDelete(
        '¿Quitar asignatura?',
        `Se quitará "${subject.name}" del plan.`,
        admin.studyPlans.subjects.destroy({ study_plan: props.plan.id, subject: subject.id }).url,
        { preserveState: true },
    );
};

const periods = computed(() => {
    const total = props.plan.academic_program.total_periods ?? 0;
    const used = Object.keys(props.subjectsByPeriod).map(Number);
    const max = Math.max(total, ...used, 1);

    return Array.from({ length: max }, (_, index) => index + 1);
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Planes académicos', href: admin.studyPlans.index() },
            { title: 'Detalle', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="plan.name" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            :title="`${plan.name} (v${plan.version})`"
            :description="`${plan.academic_program.name} · Vigente desde ${formatDate(plan.valid_from)}`"
            section="Planes académicos"
            :icon="Library"
            :action-icon="Pencil"
            action-label="Editar plan"
            :action-href="admin.studyPlans.edit(plan.id).url"
            action-permission="study-plans.edit"
        >
            <template #details>
                <div class="mt-2"><StatusBadge :value="plan.is_active" :label="plan.is_active ? 'Vigente' : 'No vigente'" /></div>
            </template>
        </PageHeader>

        <div class="grid gap-5 xl:grid-cols-3">
            <!-- Asignaturas por semestre -->
            <div class="flex flex-col gap-5 xl:col-span-2">
                <AdminCard
                    v-for="period in periods"
                    :key="period"
                    :title="`Semestre ${period}`"
                    :description="`${(subjectsByPeriod[period] ?? []).length} asignaturas · ${totalCredits(subjectsByPeriod[period] ?? [])} créditos`"
                    :icon="BookOpen"
                    :padding="false"
                >
                    <table v-if="(subjectsByPeriod[period] ?? []).length" class="w-full border-collapse text-left text-sm">
                        <thead class="bg-[#F7F8FA] text-xs font-semibold uppercase tracking-wide text-[#383D43]">
                            <tr>
                                <th class="px-5 py-3 sm:px-6">Asignatura</th>
                                <th class="px-5 py-3 sm:px-6">Módulo</th>
                                <th class="px-5 py-3 sm:px-6">Créditos</th>
                                <th class="px-5 py-3 sm:px-6">Tipo</th>
                                <th class="px-5 py-3 text-right sm:px-6">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E3E5E8]">
                            <tr v-for="subject in subjectsByPeriod[period]" :key="subject.id" class="hover:bg-[#F4F5F6]">
                                <td class="px-5 py-3 sm:px-6">
                                    <p class="font-semibold text-[#292D32]">{{ subject.name }}</p>
                                    <p class="text-xs text-[#858B93]">{{ subject.code }} · {{ subject.theory_hours }}T / {{ subject.practice_hours }}P</p>
                                </td>
                                <td class="px-5 py-3 text-[#525960] sm:px-6">{{ moduleName(subject.pivot.academic_module_id) ?? '—' }}</td>
                                <td class="px-5 py-3 text-[#525960] sm:px-6">{{ subject.credits ?? '—' }}</td>
                                <td class="px-5 py-3 sm:px-6">
                                    <StatusBadge
                                        :value="subject.pivot.is_required ? 'activo' : null"
                                        :label="subject.pivot.is_required ? 'Obligatoria' : 'Optativa'"
                                    />
                                </td>
                                <td class="px-5 py-3 sm:px-6">
                                    <div class="flex justify-end gap-1.5">
                                        <button v-if="canEdit" type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-[#383D43] hover:bg-[#F1F2F4] hover:text-[#292D32]" title="Editar" @click="openSubject(subject)">
                                            <Pencil class="h-4 w-4" />
                                        </button>
                                        <button v-if="canEdit" type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F1F2F4]" title="Quitar" @click="removeSubject(subject)">
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <p v-else class="px-5 py-4 text-sm italic text-[#858B93] sm:px-6">Sin asignaturas en este semestre.</p>

                    <template v-if="canEdit" #footer>
                        <AdminButton v-if="canEdit" variant="secondary" :icon="Plus" :disabled="!availableSubjects.length" @click="openSubject(null, period)">
                            Agregar asignatura
                        </AdminButton>
                    </template>
                </AdminCard>
            </div>

            <!-- Módulos -->
            <div class="flex flex-col gap-5">
                <AdminCard title="Módulos académicos" :icon="Layers">
                    <ul v-if="plan.modules.length" class="flex flex-col gap-3">
                        <li v-for="module in plan.modules" :key="module.id" class="flex items-start justify-between gap-2 rounded-xl border border-[#E3E5E8] p-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-[#292D32]">{{ module.name }}</p>
                                <p class="text-xs text-[#858B93]">
                                    {{ module.period_number ? `Semestre ${module.period_number}` : 'Todo el programa' }}
                                </p>
                            </div>
                            <div class="flex shrink-0 gap-1">
                                <button v-if="canEdit" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#383D43] hover:bg-[#F1F2F4] hover:text-[#292D32]" title="Editar" @click="openModule(module)">
                                    <Pencil class="h-4 w-4" />
                                </button>
                                <button v-if="canEdit" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F1F2F4]" title="Eliminar" @click="destroyModule(module)">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </li>
                    </ul>
                    <EmptyState v-else :icon="Layers" title="Sin módulos" description="Los módulos son opcionales." />

                    <template v-if="canEdit" #footer>
                        <AdminButton v-if="canEdit" variant="secondary" :icon="Plus" @click="openModule()">Agregar módulo</AdminButton>
                    </template>
                </AdminCard>

                <AttachmentsCard attachable-type="study_plan" :attachable-id="plan.id" :attachments="plan.attachments" />
            </div>
        </div>

        <!-- Diálogo de módulo -->
        <FormDialog
            v-model:open="moduleDialog"
            :title="editingModule ? 'Editar módulo' : 'Nuevo módulo'"
            :loading="moduleForm.processing"
            @submit="submitModule"
        >
            <div class="md:col-span-2">
                <FormInput id="module_name" v-model="moduleForm.name" label="Nombre" :error="moduleForm.errors.name" required />
            </div>
            <FormInput id="module_period" v-model="moduleForm.period_number" type="number" min="1" label="Semestre" help="Vacío si abarca todo el programa." :error="moduleForm.errors.period_number" />
            <FormInput id="module_sort" v-model="moduleForm.sort" type="number" min="0" label="Orden" :error="moduleForm.errors.sort" />
            <div class="md:col-span-2">
                <FormTextarea id="module_description" v-model="moduleForm.description" label="Descripción" :rows="3" :error="moduleForm.errors.description" />
            </div>
        </FormDialog>

        <!-- Diálogo de asignatura -->
        <FormDialog
            v-model:open="subjectDialog"
            :title="editingSubject ? `Editar ${editingSubject.name}` : 'Agregar asignatura'"
            :loading="subjectForm.processing"
            @submit="submitSubject"
        >
            <div v-if="!editingSubject" class="md:col-span-2">
                <FormSelect
                    id="subject_id"
                    v-model="subjectForm.subject_id"
                    label="Asignatura"
                    :options="availableSubjects.map((s) => ({ value: s.id, label: `${s.code} · ${s.name}` }))"
                    :error="subjectForm.errors.subject_id"
                    required
                />
            </div>
            <FormInput id="subject_period" v-model="subjectForm.period_number" type="number" min="1" label="Semestre" :error="subjectForm.errors.period_number" required />
            <FormSelect
                id="subject_module"
                v-model="subjectForm.academic_module_id"
                label="Módulo"
                placeholder="Sin módulo"
                :options="moduleOptions"
                :error="subjectForm.errors.academic_module_id"
                nullable
            />
            <FormInput id="subject_sort" v-model="subjectForm.sort" type="number" min="0" label="Orden" :error="subjectForm.errors.sort" />
            <label class="mt-7 flex cursor-pointer items-center gap-3 text-sm font-medium text-[#292D32]">
                <input v-model="subjectForm.is_required" type="checkbox" class="form-checkbox" />
                Asignatura obligatoria
            </label>
        </FormDialog>
    </div>
</template>
