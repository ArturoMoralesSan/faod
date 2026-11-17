<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ClipboardList, Plus } from 'lucide-vue-next';
import { ref } from 'vue';

import AdminCard from '@/components/admin/AdminCard.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { formatDateTime, recordOptions } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option, Paginated } from '@/types/admin';

defineProps<{
    counts: Paginated<{
        id: number;
        status: string;
        notes: string | null;
        created_at: string;
        closed_at: string | null;
        lines_count: number;
        warehouse: Option;
        user: { name: string } | null;
        closer: { name: string } | null;
    }>;
    warehouses: Option[];
}>();

const dialogOpen = ref(false);
const form = useForm({ warehouse_id: null as number | null, notes: '' });

const submit = (): void => {
    form.transform((data) => ({ ...data, notes: data.notes || null })).post(admin.inventoryCounts.store().url, { onSuccess: () => (dialogOpen.value = false) });
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Inventario', href: admin.inventory.index() },
            { title: 'Inventario físico', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Inventario físico" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Inventario físico"
            description="Conteos de almacén. Al cerrar un conteo, las existencias se ajustan a lo contado."
            section="Inventario"
            :icon="ClipboardList"
            :action-icon="Plus"
            action-label="Nuevo conteo"
            @action="dialogOpen = true"
        />

        <AdminCard :padding="false">
            <ul v-if="counts.data.length" class="divide-y divide-[#E3E5E8]">
                <li v-for="count in counts.data" :key="count.id">
                    <Link :href="admin.inventoryCounts.show(count.id).url" class="flex items-center justify-between gap-3 px-5 py-4 hover:bg-[#FAF3F4] sm:px-6">
                        <div>
                            <p class="text-sm font-semibold text-[#292D32]">Conteo #{{ count.id }} · {{ count.warehouse.name }}</p>
                            <p class="text-xs text-[#858B93]">
                                {{ count.lines_count }} lote(s) · abierto {{ formatDateTime(count.created_at) }}<template v-if="count.user"> por {{ count.user.name }}</template>
                                <template v-if="count.closed_at"> · cerrado {{ formatDateTime(count.closed_at) }}</template>
                            </p>
                        </div>
                        <StatusBadge :value="count.status" />
                    </Link>
                </li>
            </ul>
            <EmptyState v-else :icon="ClipboardList" title="Sin conteos" description="Abre un conteo para revisar las existencias de un almacén." />
        </AdminCard>

        <FormDialog v-model:open="dialogOpen" title="Nuevo conteo" description="Se toma la existencia actual del almacén como punto de partida." submit-label="Abrir conteo" :loading="form.processing" @submit="submit">
            <div class="md:col-span-2">
                <FormSelect id="ct_wh" v-model="form.warehouse_id" label="Almacén" :options="recordOptions(warehouses)" :error="form.errors.warehouse_id" required />
            </div>
            <div class="md:col-span-2">
                <FormTextarea id="ct_notes" v-model="form.notes" label="Notas" :rows="2" />
            </div>
        </FormDialog>
    </div>
</template>
