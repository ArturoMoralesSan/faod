<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ClipboardCheck, ClipboardList, ExternalLink } from 'lucide-vue-next';
import { ref } from 'vue';

import AdminCard from '@/components/admin/AdminCard.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import ReviewDialog from '@/components/admin/ReviewDialog.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { formatDateTime } from '@/lib/admin';
import admin from '@/routes/admin';

interface Item {
    type: string;
    id: number;
    title: string;
    url: string;
    patient: string | null;
    record_number: string | null;
    student: string | null;
    teacher: string | null;
    status: string;
    updated_at: string;
}

const props = defineProps<{
    items: Item[];
    filters: { scope: string; status: string };
    isTeacher: boolean;
    decisions: string[];
}>();

const TYPE_LABELS: Record<string, string> = {
    patient_diagnosis: 'Diagnóstico',
    treatment_plan: 'Plan de tratamiento',
    treatment_procedure: 'Procedimiento',
};

const setFilter = (key: 'scope' | 'status', value: string): void => {
    router.get(admin.reviews.index().url, { ...props.filters, [key]: value }, { preserveScroll: true, replace: true });
};

const reviewOpen = ref(false);
const current = ref<Item | null>(null);

const openReview = (item: Item): void => {
    current.value = item;
    reviewOpen.value = true;
};

const tabClass = (active: boolean) => [
    'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
    active ? 'bg-[#941B2B] text-white' : 'text-[#525960] hover:bg-[#F1F2F4]',
];

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Revisiones pendientes', href: admin.reviews.index() },
        ],
    },
});
</script>

<template>
    <Head title="Revisiones pendientes" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Revisiones pendientes"
            description="Diagnósticos y tratamientos capturados por alumnos que esperan autorización docente."
            section="Atención clínica"
            :icon="ClipboardList"
        />

        <div class="flex flex-wrap gap-3">
            <div class="inline-flex rounded-xl border border-[#E3E5E8] bg-white p-1">
                <button type="button" :class="tabClass(filters.status === 'pendiente')" @click="setFilter('status', 'pendiente')">Por revisar</button>
                <button type="button" :class="tabClass(filters.status === 'correcciones')" @click="setFilter('status', 'correcciones')">En corrección</button>
            </div>
            <div v-if="isTeacher" class="inline-flex rounded-xl border border-[#E3E5E8] bg-white p-1">
                <button type="button" :class="tabClass(filters.scope === 'mine')" @click="setFilter('scope', 'mine')">Asignadas a mí</button>
                <button type="button" :class="tabClass(filters.scope === 'all')" @click="setFilter('scope', 'all')">Todas</button>
            </div>
        </div>

        <AdminCard :padding="false">
            <ul v-if="items.length" class="divide-y divide-[#E3E5E8]">
                <li v-for="item in items" :key="`${item.type}-${item.id}`" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#941B2B]">{{ TYPE_LABELS[item.type] ?? item.type }}</p>
                        <p class="text-sm font-semibold text-[#292D32]">{{ item.title }}</p>
                        <p class="text-xs text-[#858B93]">
                            {{ [item.record_number && item.patient ? `${item.record_number} · ${item.patient}` : item.patient, item.student ? `Alumno: ${item.student}` : null, item.teacher ? `Asignado a: ${item.teacher}` : 'Sin docente asignado', formatDateTime(item.updated_at)].filter(Boolean).join(' · ') }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <StatusBadge :value="item.status" />
                        <Link :href="item.url" class="inline-flex h-9 items-center gap-1.5 rounded-lg px-3 text-xs font-semibold text-[#525960] hover:bg-[#F1F2F4] hover:text-[#292D32]">
                            <ExternalLink class="h-4 w-4" /> Ver expediente
                        </Link>
                        <button
                            type="button"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-[#BFE3CC] px-3 text-xs font-semibold text-[#187744] hover:bg-[#E2F3E8]"
                            @click="openReview(item)"
                        >
                            <ClipboardCheck class="h-4 w-4" /> Revisar
                        </button>
                    </div>
                </li>
            </ul>

            <EmptyState
                v-else
                :icon="ClipboardCheck"
                title="Nada pendiente"
                :description="filters.status === 'pendiente' ? 'No hay registros esperando revisión.' : 'No hay registros en corrección.'"
            />
        </AdminCard>

        <ReviewDialog
            v-model:open="reviewOpen"
            :reviewable-type="current?.type ?? 'patient_diagnosis'"
            :reviewable-id="current?.id ?? null"
            :title="current?.title ?? ''"
            :decisions="decisions"
        />
    </div>
</template>
