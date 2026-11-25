<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Banknote } from 'lucide-vue-next';
import { ref } from 'vue';

import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import IncomeDialog from '@/components/admin/IncomeDialog.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { filterValue, useIndexFilters } from '@/composables/useIndexFilters';
import { enumOptions, formatDateTime, formatMoney, label } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Paginated, PayerInfo } from '@/types/admin';

interface PaymentRow {
    id: number;
    folio: string;
    method: string;
    amount: number;
    reference: string | null;
    paid_at: string;
    cancelled_at: string | null;
    payer_info: PayerInfo | null;
    receiver: { name: string } | null;
    session: { register: { name: string } } | null;
}

const props = defineProps<{
    payments: Paginated<PaymentRow>;
    filters: { search?: string; method?: string; date_from?: string; date_to?: string; status?: string };
    methods: string[];
    total: number;
    feeConcepts: { id: number; code: string; name: string; amount: number | null }[];
    openSessions: { id: number; register: { name: string } | null }[];
    canCreate: boolean;
}>();

const incomeOpen = ref(false);

const { filters, hasFilters, submit, clear } = useIndexFilters(
    () => admin.finance.payments.index().url,
    {
        search: filterValue(props.filters.search),
        method: filterValue(props.filters.method),
        date_from: filterValue(props.filters.date_from),
        date_to: filterValue(props.filters.date_to),
        status: filterValue(props.filters.status),
    },
);

const dateClass = 'h-[42px] rounded-xl border border-[#E3E5E8] bg-white px-3 text-sm';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Pagos', href: admin.finance.payments.index() },
        ],
    },
});
</script>

<template>
    <Head title="Pagos" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Pagos recibidos"
            description="Recibos emitidos. Para cobrar un adeudo, entra al estado de cuenta del alumno o paciente; para otros ingresos usa el botón."
            section="Finanzas"
            :icon="Banknote"
            :action-label="canCreate ? 'Otro ingreso' : ''"
            @action="incomeOpen = true"
        />

        <div class="w-fit rounded-2xl border border-[#E3E5E8] bg-white px-5 py-3">
            <p class="text-xs text-[#737982]">Total cobrado (vigentes, con los filtros)</p>
            <p class="text-xl font-bold text-[#187744]">{{ formatMoney(total) }}</p>
        </div>

        <DataTable
            v-model:search="filters.search"
            :pagination="payments"
            :is-empty="payments.data.length === 0"
            :empty-colspan="5"
            search-placeholder="Buscar por folio..."
            :has-filters="hasFilters"
            @search="submit"
            @clear-filters="clear"
        >
            <template #filters>
                <FilterSelect v-model="filters.method" placeholder="Todas las formas de pago" :options="enumOptions(methods)" @change="submit" />
                <FilterSelect v-model="filters.status" placeholder="Vigentes y cancelados" :options="[{ value: 'vigente', label: 'Vigentes' }, { value: 'cancelado', label: 'Cancelados' }]" @change="submit" />
                <input v-model="filters.date_from" type="date" :class="dateClass" title="Desde" @change="submit" />
                <input v-model="filters.date_to" type="date" :class="dateClass" title="Hasta" @change="submit" />
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Folio</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Pagó</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Forma</th>
                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">Monto</th>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">Estado</th>
                </tr>
            </template>

            <template #table-body>
                <tr v-for="p in payments.data" :key="p.id" class="transition-colors hover:bg-[#F4F5F6]">
                    <td class="px-5 py-4 sm:px-6">
                        <Link :href="admin.finance.payments.show(p.id).url" class="font-semibold text-[#941B2B] hover:underline">{{ p.folio }}</Link>
                        <p class="text-xs text-[#858B93]">{{ formatDateTime(p.paid_at) }}<template v-if="p.receiver"> · {{ p.receiver.name }}</template></p>
                    </td>
                    <td class="px-5 py-4 sm:px-6">
                        <Link v-if="p.payer_info?.url" :href="p.payer_info.url" class="font-semibold text-[#292D32] hover:text-[#292D32]">{{ p.payer_info.name }}</Link>
                        <p v-else-if="p.payer_info" class="font-semibold text-[#292D32]">{{ p.payer_info.name }}</p>
                        <p v-if="p.payer_info" class="text-xs text-[#858B93]">{{ [label(p.payer_info.type), p.payer_info.code].filter(Boolean).join(' · ') }}</p>
                    </td>
                    <td class="px-5 py-4 text-[#525960] sm:px-6">
                        {{ label(p.method) }}
                        <p class="text-xs text-[#858B93]">{{ [p.reference, p.session?.register.name].filter(Boolean).join(' · ') }}</p>
                    </td>
                    <td class="px-5 py-4 text-right font-semibold text-[#292D32] sm:px-6">{{ formatMoney(p.amount) }}</td>
                    <td class="px-5 py-4 sm:px-6"><StatusBadge :value="p.cancelled_at ? 'cancelado' : 'pagado'" /></td>
                </tr>
            </template>

            <template #empty>
                <EmptyState :icon="Banknote" title="Sin pagos" description="No hay pagos con esos filtros." />
            </template>
        </DataTable>

        <IncomeDialog v-if="canCreate" v-model:open="incomeOpen" :methods="methods" :open-sessions="openSessions" :fee-concepts="feeConcepts" />
    </div>
</template>
