<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { BadgePercent, Ban, Plus, Receipt, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import AdminButton from '@/components/admin/AdminButton.vue';
import ChargeDialog from '@/components/admin/ChargeDialog.vue';
import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterChip from '@/components/admin/FilterChip.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import SearchSelect from '@/components/admin/SearchSelect.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { filterValue, useIndexFilters } from '@/composables/useIndexFilters';
import { enumOptions, formatDate, formatMoney, label, recordOptions, todayInput } from '@/lib/admin';
import admin from '@/routes/admin';
import type { ChargeData, Option, Paginated } from '@/types/admin';

const props = defineProps<{
    charges: Paginated<ChargeData>;
    filters: { payer_type?: string; status?: string; fee_concept_id?: string; search?: string };
    totals: { pending: number; overdue: number };
    statuses: string[];
    concepts: (Option & { category: string; amount: number | null })[];
    periods: (Option & { is_current: boolean })[];
    cohorts: Option[];
    payerSearchUrl: string;
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.finance.charges.index().url,
    {
        payer_type: filterValue(props.filters.payer_type),
        status: filterValue(props.filters.status),
        fee_concept_id: filterValue(props.filters.fee_concept_id),
        search: filterValue(props.filters.search),
    },
);

const today = todayInput();
const conceptOptions = computed(() => recordOptions(props.concepts, true));
const payerTypes = [
    { value: 'patient', label: 'Pacientes' },
    { value: 'student', label: 'Alumnos' },
];

/* ------------------------------- Nuevo adeudo ------------------------------- */

const newOpen = ref(false);
const newForm = useForm({
    payer_type: 'patient',
    payer_id: null as number | null,
    fee_concept_id: null as number | null,
    academic_period_id: null as number | null,
    description: '',
    amount: null as number | null,
    due_date: '',
});

const openNew = (): void => {
    newForm.reset();
    newForm.clearErrors();
    newOpen.value = true;
};

const onConcept = (form: { fee_concept_id: number | null; description: string; amount: number | null }): void => {
    const concept = props.concepts.find((c) => c.id === form.fee_concept_id);

    if (concept) {
        form.description = form.description || concept.name;
        form.amount = form.amount ?? concept.amount;
    }
};

const submitNew = (): void => {
    newForm.transform((data) => ({ ...data, due_date: data.due_date || null }))
        .post(admin.finance.charges.store().url, { preserveScroll: true, onSuccess: () => (newOpen.value = false) });
};

/* ------------------------------ Cuotas masivas ------------------------------ */

const bulkOpen = ref(false);
const bulkForm = useForm({
    fee_concept_id: null as number | null,
    academic_period_id: (props.periods.find((p) => p.is_current)?.id ?? null) as number | null,
    cohort_id: null as number | null,
    description: '',
    amount: null as number | null,
    due_date: '',
});

const submitBulk = (): void => {
    bulkForm.transform((data) => ({ ...data, description: data.description || null, due_date: data.due_date || null }))
        .post(admin.finance.charges.bulk().url, { preserveScroll: true, onSuccess: () => (bulkOpen.value = false) });
};

/* ---------------------------- Descuento / cancelar ---------------------------- */

const actionOpen = ref(false);
const actionMode = ref<'discount' | 'cancel'>('discount');
const actionCharge = ref<ChargeData | null>(null);

const openAction = (charge: ChargeData, mode: 'discount' | 'cancel'): void => {
    actionCharge.value = charge;
    actionMode.value = mode;
    actionOpen.value = true;
};

const isOpenCharge = (c: ChargeData) => ['pendiente', 'parcial'].includes(c.status);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Adeudos', href: admin.finance.charges.index() },
        ],
    },
});
</script>

<template>
    <Head title="Adeudos" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader title="Adeudos" description="Cuotas de alumnos y cargos de pacientes." section="Finanzas" :icon="Receipt" :action-icon="Plus" action-label="Nuevo adeudo" @action="openNew" />

        <div class="flex flex-wrap items-center gap-3">
            <div class="rounded-2xl border border-[#E3E5E8] bg-white px-5 py-3">
                <p class="text-xs text-[#737982]">Por cobrar</p>
                <p class="text-xl font-bold text-[#292D32]">{{ formatMoney(totals.pending) }}</p>
            </div>
            <div class="rounded-2xl border border-[#E8C7CC] bg-[#F9ECEE] px-5 py-3">
                <p class="text-xs text-[#941B2B]">Vencido</p>
                <p class="text-xl font-bold text-[#941B2B]">{{ formatMoney(totals.overdue) }}</p>
            </div>
            <span class="flex-1" />
            <AdminButton variant="secondary" :icon="Users" @click="bulkOpen = true">Generar cuotas a alumnos</AdminButton>
        </div>

        <DataTable
            v-model:search="filters.search"
            :pagination="charges"
            :is-empty="charges.data.length === 0"
            :empty-colspan="6"
            search-placeholder="Buscar por descripción..."
            :has-filters="hasFilters"
            @search="submit"
            @clear-filters="clear"
        >
            <template #filters>
                <FilterSelect v-model="filters.payer_type" placeholder="Alumnos y pacientes" :options="payerTypes" @change="submit" />
                <FilterSelect v-model="filters.status" placeholder="Todos los estados" :options="enumOptions(statuses)" @change="submit" />
                <FilterSelect v-model="filters.fee_concept_id" placeholder="Todos los conceptos" :options="conceptOptions" @change="submit" />
            </template>

            <template #active-filters>
                <FilterChip v-if="filters.search">Búsqueda: {{ filters.search }}</FilterChip>
                <FilterChip v-if="filters.status">Estado: {{ label(filters.status) }}</FilterChip>
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Adeudo</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Quién paga</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Monto</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Saldo</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Estado</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Acciones</th>
                </tr>
            </template>

            <template #table-body>
                <tr v-for="c in charges.data" :key="c.id" class="transition-colors hover:bg-[#FAF3F4]">
                    <td class="px-5 py-4 sm:px-6">
                        <p class="font-semibold text-[#292D32]">{{ c.description }}</p>
                        <p class="text-xs text-[#858B93]">
                            {{ [c.concept?.code, c.academic_period?.name, c.due_date ? `Vence ${formatDate(c.due_date)}` : null].filter(Boolean).join(' · ') }}
                            <span v-if="c.due_date && c.due_date < today && isOpenCharge(c)" class="font-semibold text-[#941B2B]"> · vencido</span>
                        </p>
                    </td>
                    <td class="px-5 py-4 sm:px-6">
                        <Link v-if="c.payer_info?.url" :href="c.payer_info.url" class="font-semibold text-[#941B2B] hover:underline">{{ c.payer_info.name }}</Link>
                        <p v-if="c.payer_info" class="text-xs text-[#858B93]">{{ label(c.payer_info.type) }} · {{ c.payer_info.code }}</p>
                    </td>
                    <td class="px-5 py-4 text-right text-[#525960] sm:px-6">
                        {{ formatMoney(c.amount) }}
                        <p v-if="c.discount_amount" class="text-xs text-[#187744]">−{{ formatMoney(c.discount_amount) }}</p>
                    </td>
                    <td class="px-5 py-4 text-right font-semibold text-[#292D32] sm:px-6">{{ formatMoney(c.balance) }}</td>
                    <td class="px-5 py-4 sm:px-6"><StatusBadge :value="c.status" /></td>
                    <td class="px-5 py-4 sm:px-6">
                        <div v-if="isOpenCharge(c)" class="flex justify-end gap-1">
                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#383D43] hover:bg-[#F9ECEE]" title="Descuento" @click="openAction(c, 'discount')">
                                <BadgePercent class="h-4 w-4" />
                            </button>
                            <button v-if="!c.paid_amount" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F9ECEE]" title="Cancelar" @click="openAction(c, 'cancel')">
                                <Ban class="h-4 w-4" />
                            </button>
                        </div>
                    </td>
                </tr>
            </template>

            <template #empty>
                <EmptyState :icon="Receipt" title="Sin adeudos" :description="hasFilters ? 'Prueba con otros filtros.' : 'No hay adeudos registrados.'" />
            </template>
        </DataTable>

        <FormDialog v-model:open="newOpen" title="Nuevo adeudo" :loading="newForm.processing" @submit="submitNew">
            <FormSelect id="nc_type" v-model="newForm.payer_type" label="Se le cobra a" :options="[{ value: 'patient', label: 'Paciente' }, { value: 'student', label: 'Alumno' }]" required @change="newForm.payer_id = null" />
            <div>
                <SearchSelect
                    :id="`nc_payer_${newForm.payer_type}`"
                    :key="newForm.payer_type"
                    v-model="newForm.payer_id"
                    :label="newForm.payer_type === 'student' ? 'Alumno' : 'Paciente'"
                    :url="`${payerSearchUrl}?type=${newForm.payer_type}`"
                    :error="newForm.errors.payer_id"
                    required
                />
            </div>
            <div class="md:col-span-2" @change="onConcept(newForm)">
                <FormSelect id="nc_concept" v-model="newForm.fee_concept_id" label="Concepto" placeholder="Sin concepto" :options="conceptOptions" nullable />
            </div>
            <div class="md:col-span-2">
                <FormInput id="nc_desc" v-model="newForm.description" label="Descripción" :error="newForm.errors.description" required />
            </div>
            <FormInput id="nc_amount" v-model="newForm.amount" type="number" step="0.01" min="0.01" label="Monto" :error="newForm.errors.amount" required />
            <FormInput id="nc_due" v-model="newForm.due_date" type="date" label="Vence" :error="newForm.errors.due_date" />
            <FormSelect id="nc_period" v-model="newForm.academic_period_id" label="Periodo" placeholder="Sin periodo" :options="recordOptions(periods)" nullable />
        </FormDialog>

        <FormDialog v-model:open="bulkOpen" title="Generar cuotas a alumnos" description="Crea el adeudo a cada alumno con inscripción activa. No se duplica si ya lo tiene en el mismo periodo." submit-label="Generar" :loading="bulkForm.processing" @submit="submitBulk">
            <div class="md:col-span-2" @change="onConcept(bulkForm)">
                <FormSelect id="bk_concept" v-model="bulkForm.fee_concept_id" label="Concepto" :options="conceptOptions" :error="bulkForm.errors.fee_concept_id" required />
            </div>
            <FormSelect id="bk_period" v-model="bulkForm.academic_period_id" label="Periodo" placeholder="Sin periodo" :options="recordOptions(periods)" nullable />
            <FormSelect id="bk_cohort" v-model="bulkForm.cohort_id" label="Generación" placeholder="Todas" :options="recordOptions(cohorts)" nullable />
            <FormInput id="bk_amount" v-model="bulkForm.amount" type="number" step="0.01" min="0.01" label="Monto" help="Vacío = monto del concepto." :error="bulkForm.errors.amount" />
            <FormInput id="bk_due" v-model="bulkForm.due_date" type="date" label="Vence" :error="bulkForm.errors.due_date" />
            <div class="md:col-span-2">
                <FormInput id="bk_desc" v-model="bulkForm.description" label="Descripción" help="Vacío = nombre del concepto." />
            </div>
        </FormDialog>

        <ChargeDialog v-model:open="actionOpen" :charge="actionCharge" :mode="actionMode" />
    </div>
</template>
