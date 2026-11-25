<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, FileChartColumn, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import EmptyState from '@/components/admin/EmptyState.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { reportIcon } from '@/lib/reports';
import admin from '@/routes/admin';

interface ReportCard {
    key: string;
    title: string;
    description: string;
    section: string;
    icon: string;
}

const props = defineProps<{ reports: ReportCard[] }>();

const search = ref('');

const normalize = (text: string) => text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

const sections = computed(() => {
    const term = normalize(search.value.trim());
    const groups = new Map<string, ReportCard[]>();

    for (const report of props.reports) {
        if (term && !normalize(`${report.title} ${report.description} ${report.section}`).includes(term)) {
            continue;
        }

        groups.set(report.section, [...(groups.get(report.section) ?? []), report]);
    }

    return [...groups.entries()].map(([name, reports]) => ({ name, reports }));
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Reportes', href: admin.reports.index() },
        ],
    },
});
</script>

<template>
    <Head title="Reportes" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Reportes"
            description="Consulta en pantalla y descarga en PDF o Excel (CSV)."
            section="Reportes"
            :icon="FileChartColumn"
        />

        <div class="relative max-w-md">
            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#858B93]" />
            <input v-model="search" type="search" placeholder="Buscar reporte..." class="form-input pl-10" aria-label="Buscar reporte" />
        </div>

        <section v-for="section in sections" :key="section.name" class="flex flex-col gap-3">
            <h2 class="text-xs font-bold uppercase tracking-[0.14em] text-[#737982]">{{ section.name }}</h2>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <Link
                    v-for="report in section.reports"
                    :key="report.key"
                    :href="admin.reports.show(report.key).url"
                    class="group flex items-start gap-4 rounded-2xl border border-[#E3E5E8] bg-white p-5 transition-all duration-200 hover:-translate-y-0.5 hover:border-[#B8BEC6] hover:shadow-[0_8px_24px_-12px_rgba(41,45,50,0.22)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#941B2B] motion-reduce:transition-none motion-reduce:hover:translate-y-0"
                >
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B] transition-colors group-hover:bg-[#34383D] group-hover:text-white">
                        <component :is="reportIcon(report.icon)" class="h-5 w-5" />
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="flex items-center justify-between gap-2">
                            <span class="font-['Manrope',sans-serif] text-[15px] font-bold text-[#292D32]">{{ report.title }}</span>
                            <ArrowRight class="h-4 w-4 shrink-0 text-[#C9CCD1] transition-transform group-hover:translate-x-0.5 group-hover:text-[#292D32]" />
                        </span>
                        <span class="mt-1 block text-sm leading-snug text-[#737982]">{{ report.description }}</span>
                    </span>
                </Link>
            </div>
        </section>

        <EmptyState
            v-if="!sections.length"
            :icon="FileChartColumn"
            :title="reports.length ? 'Sin coincidencias' : 'Sin reportes disponibles'"
            :description="reports.length ? 'Prueba con otra búsqueda.' : 'Tu rol no tiene permiso para consultar ningún reporte.'"
        />
    </div>
</template>
