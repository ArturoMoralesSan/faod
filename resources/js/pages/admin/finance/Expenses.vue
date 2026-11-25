<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { HandCoins, Paperclip, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { filterValue, useIndexFilters } from '@/composables/useIndexFilters';
import { confirmDelete, enumOptions, formatDateTime, formatMoney, label, recordOptions, toDateTimeInput } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Attachment, Option, Paginated, SelectOption } from '@/types/admin';

interface Expense {
    id: number;
    category: string;
    description: string;
    amount: number;
    method: string;
    paid_at: string;
    supplier_id: number | null;
    clinic_id: number | null;
    space_id: number | null;
    cash_register_session_id: number | null;
    reference: string | null;
    notes: string | null;
    supplier: Option | null;
    clinic: Option | null;
    space: (Option & { code: string }) | null;
    creator: { name: string } | null;
    session: { register: { name: string } } | null;
    attachments: Attachment[];
}

const props = defineProps<{
    expenses: Paginated<Expense>;
    total: number;
    filters: { category?: string; clinic_id?: string; space_id?: string; date_from?: string; date_to?: string; search?: string };
    categories: string[];
    methods: string[];
    suppliers: Option[];
    clinics: Option[];
    laboratories: (Option & { code: string })[];
    openSessions: { id: number; register: Option }[];
}>();

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.finance.expenses.index().url,
    {
        search: filterValue(props.filters.search),
        category: filterValue(props.filters.category),
        clinic_id: filterValue(props.filters.clinic_id),
        space_id: filterValue(props.filters.space_id),
        date_from: filterValue(props.filters.date_from),
        date_to: filterValue(props.filters.date_to),
    },
);

/** "mantenimiento" en las etiquetas generales es el estado de un equipo ("En mantenimiento"). */
const categoryLabel = (category: string): string => (category === 'mantenimiento' ? 'Mantenimiento' : label(category));
const categoryOptions = computed<SelectOption[]>(() => props.categories.map((c) => ({ value: c, label: categoryLabel(c) })));

const sessionOptions = computed<SelectOption[]>(() => props.openSessions.map((s) => ({ value: s.id, label: s.register.name })));
const dateClass = 'h-[42px] rounded-xl border border-[#E3E5E8] bg-white px-3 text-sm';

const dialogOpen = ref(false);
const editing = ref<Expense | null>(null);

const empty = {
    category: 'material',
    description: '',
    amount: null as number | null,
    method: 'transferencia',
    paid_at: '',
    supplier_id: null as number | null,
    clinic_id: null as number | null,
    space_id: null as number | null,
    cash_register_session_id: null as number | null,
    reference: '',
    notes: '',
    file: null as File | null,
};

const form = useForm({ ...empty });

const open = (expense: Expense | null = null): void => {
    editing.value = expense;
    form.defaults({ ...empty });
    form.reset();
    form.clearErrors();

    if (expense) {
        Object.assign(form, {
            ...expense,
            paid_at: toDateTimeInput(expense.paid_at),
            reference: expense.reference ?? '',
            notes: expense.notes ?? '',
        });
    } else {
        form.paid_at = toDateTimeInput(new Date().toISOString());
        form.cash_register_session_id = sessionOptions.value.length === 1 ? (sessionOptions.value[0].value as number) : null;
    }

    dialogOpen.value = true;
};

const submitForm = (): void => {
    const options = { preserveScroll: true, onSuccess: () => (dialogOpen.value = false) };

    form.transform((data) => ({
        category: data.category,
        description: data.description,
        amount: data.amount,
        method: data.method,
        paid_at: data.paid_at,
        supplier_id: data.supplier_id,
        clinic_id: data.clinic_id,
        space_id: data.space_id,
        cash_register_session_id: data.method === 'efectivo' ? data.cash_register_session_id : null,
        reference: data.reference || null,
        notes: data.notes || null,
    }));

    if (editing.value) {
        form.put(admin.finance.expenses.update(editing.value.id).url, options);
    } else {
        form.post(admin.finance.expenses.store().url, options);
    }
};

const destroy = (expense: Expense): void => {
    confirmDelete('¿Eliminar gasto?', `Se eliminará "${expense.description}".`, admin.finance.expenses.destroy(expense.id).url, { preserveState: true });
};

// Comprobante adjunto (factura, ticket).
const fileInput = ref<HTMLInputElement | null>(null);
const uploadFor = ref<number | null>(null);

const pickFile = (expense: Expense): void => {
    uploadFor.value = expense.id;
    fileInput.value?.click();
};

const upload = (event: Event): void => {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (file && uploadFor.value) {
        router.post(admin.attachments.store().url, { attachable_type: 'expense', attachable_id: uploadFor.value, file }, { forceFormData: true, preserveScroll: true });
    }

    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Gastos', href: admin.finance.expenses.index() },
        ],
    },
});
</script>

<template>
    <Head title="Gastos" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader title="Gastos" description="Compras y pagos de la facultad, con su comprobante." section="Finanzas" :icon="HandCoins" :action-icon="Plus" action-label="Registrar gasto" @action="open()" />

        <div class="w-fit rounded-2xl border border-[#E3E5E8] bg-white px-5 py-3">
            <p class="text-xs text-[#737982]">Total con los filtros</p>
            <p class="text-xl font-bold text-[#941B2B]">{{ formatMoney(total) }}</p>
        </div>

        <DataTable
            v-model:search="filters.search"
            :pagination="expenses"
            :is-empty="expenses.data.length === 0"
            :empty-colspan="5"
            search-placeholder="Buscar por descripción..."
            :has-filters="hasFilters"
            @search="submit"
            @clear-filters="clear"
        >
            <template #filters>
                <FilterSelect v-model="filters.category" placeholder="Todas las categorías" :options="categoryOptions" @change="submit" />
                <FilterSelect v-model="filters.clinic_id" placeholder="Todas las clínicas" :options="recordOptions(clinics)" @change="submit" />
                <FilterSelect v-if="laboratories.length" v-model="filters.space_id" placeholder="Todos los laboratorios" :options="recordOptions(laboratories, true)" @change="submit" />
                <input v-model="filters.date_from" type="date" :class="dateClass" title="Desde" @change="submit" />
                <input v-model="filters.date_to" type="date" :class="dateClass" title="Hasta" @change="submit" />
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Gasto</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Proveedor</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Forma</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Monto</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Acciones</th>
                </tr>
            </template>

            <template #table-body>
                <tr v-for="e in expenses.data" :key="e.id" class="transition-colors hover:bg-[#F4F5F6]">
                    <td class="px-5 py-4 sm:px-6">
                        <p class="font-semibold text-[#292D32]">{{ e.description }}</p>
                        <p class="text-xs text-[#858B93]">{{ [categoryLabel(e.category), formatDateTime(e.paid_at), e.clinic?.name, e.space ? `Lab. ${e.space.name}` : null].filter(Boolean).join(' · ') }}</p>
                        <p v-if="e.attachments.length" class="mt-1 flex flex-wrap gap-2 text-xs">
                            <a v-for="a in e.attachments" :key="a.id" :href="admin.attachments.download(a.id).url" class="text-[#941B2B] hover:underline">{{ a.title }}</a>
                        </p>
                    </td>
                    <td class="px-5 py-4 text-[#525960] sm:px-6">{{ e.supplier?.name ?? '—' }}</td>
                    <td class="px-5 py-4 text-[#525960] sm:px-6">
                        {{ label(e.method) }}
                        <p class="text-xs text-[#858B93]">{{ [e.reference, e.session?.register.name].filter(Boolean).join(' · ') }}</p>
                    </td>
                    <td class="px-5 py-4 text-right font-semibold text-[#292D32] sm:px-6">{{ formatMoney(e.amount) }}</td>
                    <td class="px-5 py-4 sm:px-6">
                        <div class="flex justify-end gap-1">
                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#383D43] hover:bg-[#F1F2F4]" title="Adjuntar comprobante" @click="pickFile(e)">
                                <Paperclip class="h-4 w-4" />
                            </button>
                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#383D43] hover:bg-[#F1F2F4]" title="Editar" @click="open(e)">
                                <Pencil class="h-4 w-4" />
                            </button>
                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F1F2F4]" title="Eliminar" @click="destroy(e)">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </td>
                </tr>
            </template>

            <template #empty>
                <EmptyState :icon="HandCoins" title="Sin gastos" description="No hay gastos con esos filtros." />
            </template>
        </DataTable>

        <input ref="fileInput" type="file" class="hidden" accept=".pdf,.jpg,.jpeg,.png,.webp,.xml" @change="upload" />

        <FormDialog v-model:open="dialogOpen" :title="editing ? 'Editar gasto' : 'Registrar gasto'" :loading="form.processing" @submit="submitForm">
            <div class="md:col-span-2">
                <FormInput id="ex_desc" v-model="form.description" label="Descripción" :error="form.errors.description" required />
            </div>
            <FormSelect id="ex_cat" v-model="form.category" label="Categoría" :options="categoryOptions" :error="form.errors.category" required />
            <FormInput id="ex_amount" v-model="form.amount" type="number" step="0.01" min="0.01" label="Monto" :error="form.errors.amount" required />
            <FormSelect id="ex_method" v-model="form.method" label="Forma de pago" :options="enumOptions(methods)" :error="form.errors.method" required />
            <FormInput id="ex_date" v-model="form.paid_at" type="datetime-local" label="Fecha" :error="form.errors.paid_at" required />
            <div v-if="form.method === 'efectivo'" class="md:col-span-2">
                <FormSelect id="ex_session" v-model="form.cash_register_session_id" label="Caja de donde sale" :placeholder="sessionOptions.length ? 'Elige la caja' : 'No hay cajas abiertas'" :options="sessionOptions" :error="form.errors.cash_register_session_id" required />
            </div>
            <FormSelect id="ex_supplier" v-model="form.supplier_id" label="Proveedor" placeholder="Sin proveedor" :options="recordOptions(suppliers)" nullable />
            <FormSelect id="ex_clinic" v-model="form.clinic_id" label="Clínica" placeholder="General" :options="recordOptions(clinics)" nullable />
            <FormSelect id="ex_lab" v-model="form.space_id" label="Laboratorio" placeholder="No es de un laboratorio" :options="recordOptions(laboratories, true)" :error="form.errors.space_id" nullable />
            <div class="md:col-span-2">
                <FormInput id="ex_ref" v-model="form.reference" label="Referencia" placeholder="Factura, folio fiscal…" :error="form.errors.reference" />
            </div>
            <div class="md:col-span-2">
                <FormTextarea id="ex_notes" v-model="form.notes" label="Notas" :rows="2" />
            </div>
            <p v-if="(form.errors as Record<string, string>).expense" class="form-error md:col-span-2">{{ (form.errors as Record<string, string>).expense }}</p>
        </FormDialog>
    </div>
</template>
