<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowDownToLine, ArrowLeftRight, ArrowUpFromLine, Ban, GraduationCap, Info, Package, Pencil, ScrollText, SlidersHorizontal, Trash2, Undo2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import AdminButton from '@/components/admin/AdminButton.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import DetailItem from '@/components/admin/DetailItem.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import InventoryItemDialog from '@/components/admin/InventoryItemDialog.vue';
import MovementTable from '@/components/admin/MovementTable.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { confirmDelete, formatDate, formatMoney, formatQty, label, recordOptions, todayInput } from '@/lib/admin';
import admin from '@/routes/admin';
import type { InventoryItemData, MovementRow, Option, SelectOption } from '@/types/admin';

type Operation = 'entrada' | 'salida' | 'entrega' | 'devolucion' | 'merma' | 'danado' | 'ajuste' | 'traspaso';

interface Lot {
    id: number;
    warehouse_id: number;
    lot_number: string | null;
    expires_at: string | null;
    quantity: number;
    unit_cost: number | null;
    received_at: string | null;
    warehouse: { id: number; code: string; name: string };
    supplier: Option | null;
}

const props = defineProps<{
    item: InventoryItemData & { default_supplier: Option | null };
    stock: number;
    lots: Lot[];
    movements: MovementRow[];
    expirationAlertDays: number;
    warehouses: Option[];
    suppliers: Option[];
    categories: string[];
    kinds: string[];
    allLots: { id: number; warehouse_id: number; lot_number: string | null; expires_at: string | null; quantity: number; warehouse: { code: string } }[];
    students: SelectOption[];
}>();

const today = todayInput();

const lotState = (lot: Lot) => {
    if (!lot.expires_at) return null;
    const date = lot.expires_at.substring(0, 10);
    if (date < today) return 'caducado';
    const limit = new Date();
    limit.setDate(limit.getDate() + props.expirationAlertDays);

    return date <= limit.toISOString().substring(0, 10) ? 'por_caducar' : null;
};

const byWarehouse = computed(() =>
    props.warehouses
        .map((w) => ({ warehouse: w, total: props.lots.filter((l) => l.warehouse_id === w.id).reduce((sum, l) => sum + l.quantity, 0) }))
        .filter((row) => row.total > 0),
);

const lotOptions = computed<SelectOption[]>(() =>
    props.lots
        .filter((l) => !form.warehouse_id || l.warehouse_id === form.warehouse_id)
        .map((l) => ({
            value: l.id,
            label: `${l.warehouse.code} · ${l.lot_number ? `Lote ${l.lot_number}` : 'Sin lote'}${l.expires_at ? ` · cad. ${formatDate(l.expires_at)}` : ''} · ${formatQty(l.quantity)} ${props.item.unit}`,
        })),
);

/** Para devolver al lote original: todos los lotes del almacén, incluso vacíos. */
const returnLotOptions = computed<SelectOption[]>(() =>
    props.allLots
        .filter((l) => !form.warehouse_id || l.warehouse_id === form.warehouse_id)
        .map((l) => ({
            value: l.id,
            label: `${l.warehouse.code} · ${l.lot_number ? `Lote ${l.lot_number}` : 'Sin lote'}${l.expires_at ? ` · cad. ${formatDate(l.expires_at)}` : ''}`,
        })),
);

/** Salidas de un lote con existencia (salida, entrega, merma, dañado). */
const isWithdrawal = computed(() => ['salida', 'entrega', 'merma', 'danado'].includes(operation.value));

/* --------------------------------- Movimientos --------------------------------- */

const operation = ref<Operation>('entrada');
const dialogOpen = ref(false);

const form = useForm({
    operation: 'entrada' as Operation,
    warehouse_id: null as number | null,
    to_warehouse_id: null as number | null,
    inventory_lot_id: null as number | null,
    quantity: null as number | null,
    new_quantity: null as number | null,
    lot_number: '',
    expires_at: '',
    supplier_id: null as number | null,
    unit_cost: null as number | null,
    student_profile_id: null as number | null,
    reference: '',
    reason: '',
});

const TITLES: Record<Operation, string> = {
    entrada: 'Registrar entrada',
    salida: 'Registrar salida',
    entrega: 'Entregar material a un alumno',
    devolucion: 'Registrar devolución',
    merma: 'Registrar merma o pérdida',
    danado: 'Registrar producto dañado',
    ajuste: 'Ajustar existencia de un lote',
    traspaso: 'Traspasar entre almacenes',
};

const openOperation = (op: Operation): void => {
    operation.value = op;
    form.reset();
    form.clearErrors();
    form.operation = op;
    form.warehouse_id = props.warehouses.length === 1 ? props.warehouses[0].id : null;
    form.supplier_id = props.item.default_supplier_id;
    dialogOpen.value = true;
};

const submit = (): void => {
    form.transform((data) => Object.fromEntries(Object.entries(data).map(([k, v]) => [k, v === '' ? null : v])))
        .post(admin.inventory.movements.store(props.item.id).url, { preserveScroll: true, onSuccess: () => (dialogOpen.value = false) });
};

const editOpen = ref(false);

const destroy = (): void => {
    confirmDelete('¿Eliminar artículo?', 'Solo se puede eliminar si no tiene existencia.', admin.inventory.destroy(props.item.id).url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Inventario', href: admin.inventory.index() },
            { title: 'Artículo', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="item.name" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader :title="item.name" :description="[item.code, label(item.kind), item.category].filter(Boolean).join(' · ')" section="Inventario" :icon="Package">
            <template #details>
                <div class="mt-2"><StatusBadge :value="item.is_active" /></div>
            </template>
        </PageHeader>

        <div class="flex flex-wrap gap-2">
            <AdminButton :icon="ArrowDownToLine" @click="openOperation('entrada')">Entrada</AdminButton>
            <AdminButton variant="secondary" :icon="ArrowUpFromLine" :disabled="!lots.length" @click="openOperation('salida')">Salida</AdminButton>
            <AdminButton variant="secondary" :icon="GraduationCap" :disabled="!lots.length" @click="openOperation('entrega')">Entregar a alumno</AdminButton>
            <AdminButton variant="secondary" :icon="Undo2" @click="openOperation('devolucion')">Devolución</AdminButton>
            <AdminButton variant="secondary" :icon="Ban" :disabled="!lots.length" @click="openOperation('merma')">Merma o dañado</AdminButton>
            <AdminButton variant="secondary" :icon="ArrowLeftRight" :disabled="!lots.length" @click="openOperation('traspaso')">Traspaso</AdminButton>
            <AdminButton variant="secondary" :icon="SlidersHorizontal" :disabled="!lots.length" @click="openOperation('ajuste')">Ajuste</AdminButton>
            <AdminButton variant="secondary" :icon="Pencil" @click="editOpen = true">Editar artículo</AdminButton>
            <AdminButton variant="secondary" :icon="Trash2" @click="destroy">Eliminar</AdminButton>
        </div>

        <div class="grid gap-5 xl:grid-cols-3">
            <AdminCard title="Existencia" :icon="Info">
                <p :class="['text-3xl font-bold', item.minimum_stock > 0 && stock < item.minimum_stock ? 'text-[#941B2B]' : 'text-[#292D32]']">
                    {{ formatQty(stock) }} <span class="text-base font-semibold text-[#737982]">{{ item.unit }}</span>
                </p>
                <p class="text-xs text-[#858B93]">Mínimo: {{ formatQty(item.minimum_stock) }} {{ item.unit }}</p>

                <ul v-if="byWarehouse.length" class="mt-4 divide-y divide-[#E3E5E8]">
                    <li v-for="row in byWarehouse" :key="row.warehouse.id" class="flex justify-between py-2 text-sm">
                        <span class="text-[#525960]">{{ row.warehouse.name }}</span>
                        <span class="font-semibold text-[#292D32]">{{ formatQty(row.total) }}</span>
                    </li>
                </ul>

                <dl class="mt-4 grid grid-cols-1 gap-3">
                    <DetailItem label="Proveedor habitual" :value="item.default_supplier?.name" />
                    <DetailItem label="Caducidad" :value="item.tracks_expiration ? 'Controla lote y caducidad' : 'No aplica'" />
                </dl>
                <p v-if="item.description" class="mt-3 whitespace-pre-line text-sm text-[#525960]">{{ item.description }}</p>
            </AdminCard>

            <AdminCard title="Lotes con existencia" :icon="Package" class="xl:col-span-2" :padding="false">
                <table v-if="lots.length" class="w-full text-left text-sm">
                    <thead class="bg-[#F7F8FA] text-xs font-semibold uppercase tracking-wide text-[#383D43]">
                        <tr>
                            <th class="px-4 py-3">Almacén</th>
                            <th class="px-4 py-3">Lote</th>
                            <th class="px-4 py-3">Caducidad</th>
                            <th class="px-4 py-3 text-right">Cantidad</th>
                            <th class="px-4 py-3 text-right">Costo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E3E5E8]">
                        <tr v-for="lot in lots" :key="lot.id">
                            <td class="px-4 py-3 text-[#525960]">{{ lot.warehouse.name }}</td>
                            <td class="px-4 py-3 text-[#525960]">{{ lot.lot_number ?? '—' }}<p v-if="lot.supplier" class="text-xs text-[#858B93]">{{ lot.supplier.name }}</p></td>
                            <td class="px-4 py-3">
                                <span v-if="lot.expires_at" :class="lotState(lot) === 'caducado' ? 'font-semibold text-[#941B2B]' : lotState(lot) === 'por_caducar' ? 'font-semibold text-[#A64B00]' : 'text-[#525960]'">
                                    {{ formatDate(lot.expires_at) }}{{ lotState(lot) === 'caducado' ? ' (caducado)' : '' }}
                                </span>
                                <span v-else class="text-[#858B93]">—</span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-[#292D32]">{{ formatQty(lot.quantity) }}</td>
                            <td class="px-4 py-3 text-right text-[#525960]">{{ lot.unit_cost !== null ? formatMoney(lot.unit_cost) : '—' }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="px-5 py-4 text-sm italic text-[#858B93]">Sin existencia. Registra una entrada.</p>
            </AdminCard>
        </div>

        <AdminCard title="Últimos movimientos" :icon="ScrollText" :padding="false">
            <MovementTable v-if="movements.length" :movements="movements" :unit="item.unit" />
            <p v-else class="px-5 py-4 text-sm italic text-[#858B93]">Sin movimientos.</p>
            <template #footer>
                <Link :href="admin.inventory.movements.index({ query: { inventory_item_id: item.id } }).url" class="text-sm font-semibold text-[#941B2B] hover:underline">
                    Ver kárdex completo
                </Link>
            </template>
        </AdminCard>

        <FormDialog v-model:open="dialogOpen" :title="TITLES[operation]" :loading="form.processing" @submit="submit">
            <template v-if="operation !== 'ajuste'">
                <FormSelect id="mv_wh" v-model="form.warehouse_id" :label="operation === 'traspaso' ? 'Almacén de origen' : 'Almacén'" :options="recordOptions(warehouses)" :error="form.errors.warehouse_id" required />
                <FormInput id="mv_qty" v-model="form.quantity" type="number" step="0.01" min="0.01" :label="`Cantidad (${item.unit})`" :error="form.errors.quantity" required />
            </template>

            <template v-if="operation === 'entrada'">
                <FormInput id="mv_lot" v-model="form.lot_number" label="Lote" :error="form.errors.lot_number" />
                <FormInput id="mv_exp" v-model="form.expires_at" type="date" label="Caducidad" :error="form.errors.expires_at" :required="item.tracks_expiration" />
                <FormSelect id="mv_sup" v-model="form.supplier_id" label="Proveedor" placeholder="Sin proveedor" :options="recordOptions(suppliers)" nullable />
                <FormInput id="mv_cost" v-model="form.unit_cost" type="number" step="0.01" min="0" label="Costo unitario" :error="form.errors.unit_cost" />
                <FormInput id="mv_ref" v-model="form.reference" label="Referencia" placeholder="Factura, remisión…" :error="form.errors.reference" />
                <FormInput id="mv_reason" v-model="form.reason" label="Motivo" placeholder="Compra, donación…" :error="form.errors.reason" />
            </template>

            <template v-else-if="isWithdrawal">
                <div v-if="operation === 'merma' || operation === 'danado'" class="md:col-span-2 flex gap-2">
                    <button
                        v-for="type in (['merma', 'danado'] as const)"
                        :key="type"
                        type="button"
                        class="flex-1 rounded-xl border px-3 py-2 text-sm font-semibold transition-colors"
                        :class="operation === type ? 'border-[#941B2B] bg-[#F9ECEE] text-[#941B2B]' : 'border-[#E3E5E8] text-[#525960] hover:border-[#B8BEC6]'"
                        @click="operation = type; form.operation = type"
                    >
                        {{ type === 'merma' ? 'Merma o pérdida' : 'Producto dañado' }}
                    </button>
                </div>
                <div v-if="operation === 'entrega'" class="md:col-span-2">
                    <FormSelect id="mv_student" v-model="form.student_profile_id" label="Alumno que recibe" :options="students" :error="form.errors.student_profile_id" required />
                </div>
                <div class="md:col-span-2">
                    <FormSelect id="mv_lot_out" v-model="form.inventory_lot_id" label="Lote" placeholder="Automático (primero lo que caduca antes)" :options="lotOptions" :error="form.errors.inventory_lot_id" nullable />
                </div>
                <FormInput
                    id="mv_reason_out"
                    v-model="form.reason"
                    :label="operation === 'entrega' ? 'Para qué se entrega' : 'Motivo'"
                    :placeholder="operation === 'entrega' ? 'Práctica de laboratorio, clínica…' : operation === 'salida' ? 'Préstamo, baja…' : operation === 'merma' ? 'Se extravió, se derramó…' : 'Se rompió, contaminado, defecto de fábrica…'"
                    :error="form.errors.reason"
                    :required="operation !== 'entrega'"
                />
                <FormInput id="mv_ref_out" v-model="form.reference" label="Referencia" :error="form.errors.reference" />
            </template>

            <template v-else-if="operation === 'devolucion'">
                <div class="md:col-span-2">
                    <FormSelect id="mv_student_ret" v-model="form.student_profile_id" label="Alumno que devuelve" placeholder="No viene de un alumno" :options="students" :error="form.errors.student_profile_id" nullable />
                </div>
                <div class="md:col-span-2">
                    <FormSelect id="mv_lot_ret" v-model="form.inventory_lot_id" label="Regresar al lote" placeholder="Lote nuevo (capturar lote y caducidad)" :options="returnLotOptions" :error="form.errors.inventory_lot_id" nullable />
                </div>
                <template v-if="!form.inventory_lot_id">
                    <FormInput id="mv_lot_ret_num" v-model="form.lot_number" label="Lote" :error="form.errors.lot_number" />
                    <FormInput id="mv_exp_ret" v-model="form.expires_at" type="date" label="Caducidad" :error="form.errors.expires_at" :required="item.tracks_expiration" />
                </template>
                <FormInput id="mv_reason_ret" v-model="form.reason" label="Motivo" placeholder="Sobrante de práctica, no se usó…" :error="form.errors.reason" required />
                <FormInput id="mv_ref_ret" v-model="form.reference" label="Referencia" :error="form.errors.reference" />
            </template>

            <template v-else-if="operation === 'traspaso'">
                <FormSelect id="mv_to" v-model="form.to_warehouse_id" label="Almacén de destino" :options="recordOptions(warehouses.filter((w) => w.id !== form.warehouse_id))" :error="form.errors.to_warehouse_id" required />
                <FormInput id="mv_reason_tr" v-model="form.reason" label="Motivo" :error="form.errors.reason" />
            </template>

            <template v-else>
                <div class="md:col-span-2">
                    <FormSelect id="mv_lot_adj" v-model="form.inventory_lot_id" label="Lote" :options="lotOptions" :error="form.errors.inventory_lot_id" required />
                </div>
                <FormInput id="mv_new" v-model="form.new_quantity" type="number" step="0.01" min="0" :label="`Existencia real (${item.unit})`" :error="form.errors.new_quantity" required />
                <FormInput id="mv_reason_adj" v-model="form.reason" label="Motivo" placeholder="Faltante, sobrante, error de captura…" :error="form.errors.reason" required />
            </template>
        </FormDialog>

        <InventoryItemDialog v-model:open="editOpen" :item="item" :suppliers="suppliers" :categories="categories" :kinds="kinds" />
    </div>
</template>
