<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ClipboardList, Lock, Save } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed } from 'vue';

import AdminButton from '@/components/admin/AdminButton.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { formatDate, formatQty } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option } from '@/types/admin';

interface Line {
    id: number;
    system_quantity: number;
    counted_quantity: number | null;
    lot: { lot_number: string | null; expires_at: string | null; item: { id: number; code: string; name: string; unit: string } };
}

const props = defineProps<{
    count: {
        id: number;
        status: string;
        notes: string | null;
        warehouse: Option;
        lines: Line[];
    };
}>();

const isOpen = computed(() => props.count.status === 'abierto');

const form = useForm({
    lines: [...props.count.lines]
        .sort((a, b) => a.lot.item.name.localeCompare(b.lot.item.name))
        .map((line) => ({ ...line, counted: line.counted_quantity as number | string | null })),
});

const difference = (line: { system_quantity: number; counted: number | string | null }) =>
    line.counted === null || line.counted === '' ? null : Number(line.counted) - line.system_quantity;

const summary = computed(() => ({
    captured: form.lines.filter((l) => l.counted !== null && l.counted !== '').length,
    differences: form.lines.filter((l) => (difference(l) ?? 0) !== 0).length,
}));

const save = (onSuccess?: () => void): void => {
    form.transform((data) => ({
        lines: data.lines.map((l) => ({ id: l.id, counted_quantity: l.counted === '' ? null : l.counted })),
    })).put(admin.inventoryCounts.update(props.count.id).url, { preserveScroll: true, onSuccess });
};

const close = (): void => {
    Swal.fire({
        title: '¿Cerrar el conteo?',
        text: `Se ajustarán ${summary.value.differences} lote(s) con diferencia. Los lotes sin capturar no cambian.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, cerrar y ajustar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        confirmButtonColor: '#941B2B',
        cancelButtonColor: '#737982',
    }).then((result) => {
        if (result.isConfirmed) {
            save(() => router.patch(admin.inventoryCounts.close(props.count.id).url, {}, { preserveScroll: true }));
        }
    });
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Inventario físico', href: admin.inventoryCounts.index() },
            { title: 'Conteo', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Conteo #${count.id}`" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            :title="`Conteo #${count.id} · ${count.warehouse.name}`"
            :description="`${summary.captured} de ${form.lines.length} lotes capturados · ${summary.differences} con diferencia`"
            section="Inventario físico"
            :icon="ClipboardList"
            :action-icon="ArrowLeft"
            action-label="Volver"
            :action-href="admin.inventoryCounts.index().url"
        >
            <template #details>
                <div class="mt-2"><StatusBadge :value="count.status" /></div>
            </template>
        </PageHeader>

        <AdminCard :padding="false">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#F7F8FA] text-xs font-semibold uppercase tracking-wide text-[#383D43]">
                        <tr>
                            <th class="px-4 py-3">Artículo</th>
                            <th class="px-4 py-3">Lote</th>
                            <th class="px-4 py-3 text-right">Sistema</th>
                            <th class="px-4 py-3 text-right">Contado</th>
                            <th class="px-4 py-3 text-right">Diferencia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E3E5E8]">
                        <tr v-for="line in form.lines" :key="line.id">
                            <td class="px-4 py-2">
                                <p class="font-semibold text-[#292D32]">{{ line.lot.item.name }}</p>
                                <p class="text-xs text-[#858B93]">{{ line.lot.item.code }}</p>
                            </td>
                            <td class="px-4 py-2 text-[#525960]">
                                {{ line.lot.lot_number ?? '—' }}
                                <p v-if="line.lot.expires_at" class="text-xs text-[#858B93]">Cad. {{ formatDate(line.lot.expires_at) }}</p>
                            </td>
                            <td class="px-4 py-2 text-right text-[#525960]">{{ formatQty(line.system_quantity) }} {{ line.lot.item.unit }}</td>
                            <td class="px-4 py-2 text-right">
                                <input v-if="isOpen" v-model="line.counted" type="number" step="0.01" min="0" class="form-input ml-auto h-9 w-28 py-1 text-right text-sm" />
                                <span v-else>{{ line.counted_quantity !== null ? formatQty(line.counted_quantity) : '—' }}</span>
                            </td>
                            <td :class="['px-4 py-2 text-right font-semibold', (difference(line) ?? 0) < 0 ? 'text-[#941B2B]' : (difference(line) ?? 0) > 0 ? 'text-[#187744]' : 'text-[#858B93]']">
                                {{ difference(line) === null ? '—' : `${difference(line)! > 0 ? '+' : ''}${formatQty(difference(line))}` }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-if="!form.lines.length" class="px-5 py-4 text-sm italic text-[#858B93]">El almacén no tenía existencias al abrir el conteo.</p>

            <template v-if="isOpen" #footer>
                <div class="flex flex-wrap gap-2">
                    <AdminButton :icon="Save" :loading="form.processing" loading-text="Guardando..." @click="save()">Guardar avance</AdminButton>
                    <AdminButton variant="secondary" :icon="Lock" @click="close">Cerrar y ajustar</AdminButton>
                </div>
            </template>
        </AdminCard>
    </div>
</template>
