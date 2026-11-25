<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CalendarRange, FileDown, FileSpreadsheet, FilterX, LoaderCircle, Search, TriangleAlert } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

import DataTable from '@/components/admin/DataTable.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { PERIOD_PRESETS, reportIcon } from '@/lib/reports';
import admin from '@/routes/admin';
import type { Paginated, SelectOption } from '@/types/admin';

interface FilterDefinition {
    key: string;
    label: string;
    type: 'period' | 'select' | 'search';
    options?: SelectOption[];
    placeholder?: string;
    default?: string | null;
}

interface ColumnDefinition {
    key: string;
    label: string;
    type: 'text' | 'date' | 'datetime' | 'time' | 'money' | 'number' | 'percent' | 'status';
    total: boolean;
}

interface Row {
    cells: Record<string, string>;
    raw: Record<string, string | null>;
    /** Se muestra pero no suma a los totales (p. ej. pago cancelado). */
    excluded: boolean;
}

interface Stat {
    label: string;
    value: string;
    tone?: 'positive' | 'negative' | 'warning';
}

const props = defineProps<{
    report: { key: string; title: string; description: string; section: string; icon: string };
    filters: FilterDefinition[];
    values: Record<string, string>;
    columns: ColumnDefinition[];
    rows: Paginated<Row>;
    totals: Record<string, string> | null;
    summary: Stat[];
    pdfLimit: number;
}>();

const period = computed(() => props.filters.find((filter) => filter.type === 'period'));
const selects = computed(() => props.filters.filter((filter) => filter.type === 'select'));
const searchFilter = computed(() => props.filters.find((filter) => filter.type === 'search'));

/* --------------------------------- Filtros -------------------------------- */

const keys = ['date_from', 'date_to', 'all_dates', ...props.filters.filter((f) => f.type !== 'period').map((f) => f.key)];
const form = reactive<Record<string, string>>(Object.fromEntries(keys.map((key) => [key, props.values[key] ?? ''])));

const processing = ref(false);

const query = (values: Record<string, string>) => Object.fromEntries(Object.entries(values).filter(([, value]) => value !== '' && value != null));

const submit = (): void => {
    // Sin fechas = "Todo": se avisa al servidor para que no aplique el periodo por omisión.
    form.all_dates = period.value && !form.date_from && !form.date_to ? '1' : '';

    router.get(admin.reports.show(props.report.key).url, query(form), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => (processing.value = true),
        onFinish: () => (processing.value = false),
    });
};

const applyPreset = (key: string): void => {
    const range = PERIOD_PRESETS.find((preset) => preset.key === key)?.range() ?? null;
    form.date_from = range?.[0] ?? '';
    form.date_to = range?.[1] ?? '';
    submit();
};

const activePreset = computed(() => {
    if (!props.values.date_from && !props.values.date_to) {
        return 'all';
    }

    return PERIOD_PRESETS.find((preset) => {
        const range = preset.range();

        return range && range[0] === props.values.date_from && range[1] === props.values.date_to;
    })?.key;
});

const hasFilters = computed(() => keys.some((key) => !['date_from', 'date_to', 'all_dates'].includes(key) && props.values[key]));

const clearFilters = (): void => {
    for (const key of keys) {
        if (!['date_from', 'date_to', 'all_dates'].includes(key)) {
            form[key] = '';
        }
    }

    submit();
};

/* --------------------------------- Descargas ------------------------------- */

const appliedQuery = computed(() => query(props.values));
const pdfUrl = computed(() => admin.reports.pdf(props.report.key, { query: appliedQuery.value }).url);
const csvUrl = computed(() => admin.reports.csv(props.report.key, { query: appliedQuery.value }).url);

/* ---------------------------------- Tabla ---------------------------------- */

const numeric = (column: ColumnDefinition) => ['money', 'number', 'percent'].includes(column.type);

const toneClasses: Record<string, string> = {
    positive: 'text-[#187744]',
    negative: 'text-[#941B2B]',
    warning: 'text-[#8A5A00]',
};

const toneBar: Record<string, string> = {
    positive: 'bg-[#187744]',
    negative: 'bg-[#941B2B]',
    warning: 'bg-[#D99A1E]',
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Reportes', href: admin.reports.index() },
            { title: 'Reporte', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="report.title" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <PageHeader class="flex-1" :title="report.title" :description="report.description" :section="report.section" :icon="reportIcon(report.icon)" />

            <div class="flex shrink-0 flex-wrap gap-2">
                <Link
                    :href="admin.reports.index().url"
                    class="inline-flex h-11 items-center gap-2 rounded-full border border-[#E3E5E8] bg-white px-5 text-[13px] font-semibold text-[#383D43] transition-colors hover:border-[#B8BEC6] hover:text-[#292D32]"
                >
                    <ArrowLeft class="h-4 w-4" /> Reportes
                </Link>
                <a
                    :href="csvUrl"
                    class="inline-flex h-11 items-center gap-2 rounded-full border border-[#BFE3CC] bg-[#E2F3E8] px-5 text-[13px] font-semibold text-[#187744] transition-colors hover:border-[#187744]"
                    title="Archivo CSV que se abre en Excel"
                >
                    <FileSpreadsheet class="h-4 w-4" /> Excel (CSV)
                </a>
                <a
                    :href="pdfUrl"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex h-11 items-center gap-2 rounded-full border border-[#941B2B] bg-[#941B2B] px-5 text-[13px] font-semibold text-white transition-colors hover:border-[#941B2B] hover:bg-white hover:text-[#292D32]"
                >
                    <FileDown class="h-4 w-4" /> PDF
                </a>
            </div>
        </div>

        <!-- Filtros -->
        <section class="rounded-2xl border border-[#E3E5E8] bg-white p-4 sm:p-5">
            <div v-if="period" class="mb-4 flex flex-col gap-3 border-b border-[#E3E5E8] pb-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="mr-1 inline-flex items-center gap-1.5 text-xs font-semibold text-[#525960]">
                        <CalendarRange class="h-4 w-4 text-[#941B2B]" /> {{ period.label }}
                    </span>
                    <button
                        v-for="preset in PERIOD_PRESETS"
                        :key="preset.key"
                        type="button"
                        class="rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors"
                        :class="activePreset === preset.key ? 'border-[#941B2B] bg-[#941B2B] text-white' : 'border-[#E3E5E8] bg-white text-[#525960] hover:border-[#B8BEC6] hover:text-[#292D32]'"
                        :aria-pressed="activePreset === preset.key"
                        @click="applyPreset(preset.key)"
                    >
                        {{ preset.label }}
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <input v-model="form.date_from" type="date" class="form-input h-[38px] w-auto text-sm" aria-label="Desde" @change="submit" />
                    <span class="text-xs text-[#858B93]">al</span>
                    <input v-model="form.date_to" type="date" class="form-input h-[38px] w-auto text-sm" aria-label="Hasta" @change="submit" />
                </div>
            </div>

            <form class="flex flex-wrap items-center gap-2" @submit.prevent="submit">
                <div v-if="searchFilter" class="relative min-w-[220px] flex-[2]">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#858B93]" />
                    <input v-model="form[searchFilter.key]" type="search" :placeholder="searchFilter.placeholder" class="form-input h-[42px] pl-10" :aria-label="searchFilter.label" />
                </div>

                <FilterSelect
                    v-for="filter in selects"
                    :key="filter.key"
                    v-model="form[filter.key]"
                    :placeholder="filter.placeholder ?? filter.label"
                    :options="filter.options ?? []"
                    :aria-label="filter.label"
                    @change="submit"
                />

                <button
                    v-if="searchFilter"
                    type="submit"
                    class="inline-flex h-[42px] items-center gap-2 rounded-full border border-[#941B2B] bg-[#941B2B] px-5 text-[13px] font-semibold text-white hover:bg-white hover:text-[#292D32] hover:ring-1 hover:ring-inset hover:ring-[#941B2B]"
                >
                    <Search class="h-4 w-4" /> Buscar
                </button>

                <button
                    v-if="hasFilters"
                    type="button"
                    class="inline-flex h-[42px] items-center gap-1.5 rounded-full px-3 text-xs font-semibold text-[#941B2B] hover:bg-[#F1F2F4]"
                    @click="clearFilters"
                >
                    <FilterX class="h-4 w-4" /> Limpiar filtros
                </button>

                <LoaderCircle v-if="processing" class="h-5 w-5 animate-spin text-[#941B2B]" aria-label="Actualizando" />
            </form>
        </section>

        <!-- Indicadores -->
        <section v-if="summary.length" class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
            <div v-for="stat in summary" :key="stat.label" class="relative overflow-hidden rounded-2xl border border-[#E3E5E8] bg-white px-4 py-4">
                <span class="absolute inset-x-0 top-0 h-1" :class="stat.tone ? toneBar[stat.tone] : 'bg-[#E3E5E8]'" />
                <p class="text-[11px] font-semibold uppercase tracking-wide text-[#737982]">{{ stat.label }}</p>
                <p class="mt-1 font-['Manrope',sans-serif] text-xl font-bold leading-tight sm:text-2xl" :class="stat.tone ? toneClasses[stat.tone] : 'text-[#292D32]'">
                    {{ stat.value }}
                </p>
            </div>
        </section>

        <p v-if="rows.total > pdfLimit" class="flex items-center gap-2 rounded-xl border border-[#F1DDB0] bg-[#FDF6E7] px-4 py-3 text-sm text-[#8A5A00]">
            <TriangleAlert class="h-4 w-4 shrink-0" />
            Hay {{ rows.total.toLocaleString('es-MX') }} registros. El PDF incluye los primeros {{ pdfLimit.toLocaleString('es-MX') }}; el CSV trae todos.
        </p>

        <!-- Vista previa -->
        <DataTable :pagination="rows" :is-empty="rows.data.length === 0" :empty-colspan="columns.length" :show-search="false" :class="{ 'opacity-60 transition-opacity': processing }">
            <template #table-head>
                <tr>
                    <th v-for="column in columns" :key="column.key" class="whitespace-nowrap px-4 py-3.5 font-semibold text-[#383D43]" :class="{ 'text-right': numeric(column) }">
                        {{ column.label }}
                    </th>
                </tr>
            </template>

            <template #table-body>
                <tr v-for="(row, index) in rows.data" :key="index" class="transition-colors hover:bg-[#F4F5F6]">
                    <td
                        v-for="column in columns"
                        :key="column.key"
                        class="px-4 py-3"
                        :class="[
                            numeric(column) ? 'whitespace-nowrap text-right tabular-nums' : '',
                            row.raw[column.key] === 'negative' ? 'text-[#941B2B]' : 'text-[#383D43]',
                            column.type === 'date' || column.type === 'datetime' ? 'whitespace-nowrap' : '',
                            column.total && row.excluded ? 'text-[#A3A8AF] line-through' : '',
                        ]"
                    >
                        <StatusBadge v-if="column.type === 'status' && row.raw[column.key]" :value="row.raw[column.key]" :label="row.cells[column.key]" />
                        <template v-else>{{ row.cells[column.key] }}</template>
                    </td>
                </tr>

                <tr v-if="totals && rows.data.length" class="bg-[#F7F8FA] font-semibold">
                    <td v-for="(column, index) in columns" :key="column.key" class="whitespace-nowrap border-t-2 border-[#292D32]/70 px-4 py-3 tabular-nums text-[#292D32]" :class="{ 'text-right': numeric(column) }">
                        <template v-if="totals[column.key]">{{ totals[column.key] }}</template>
                        <span v-else-if="index === 0" class="text-xs uppercase tracking-wide text-[#525960]">Total de {{ rows.total.toLocaleString('es-MX') }} registros</span>
                    </td>
                </tr>
            </template>

            <template #empty>
                <EmptyState :icon="reportIcon(report.icon)" title="Sin registros" description="No hay datos con estos filtros. Prueba con otro periodo." />
            </template>
        </DataTable>
    </div>
</template>
