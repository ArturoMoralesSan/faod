<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Lock, Printer, Wallet } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed } from 'vue';

import AdminButton from '@/components/admin/AdminButton.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { formatDateTime, formatMoney, label } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option, PayerInfo } from '@/types/admin';

const props = defineProps<{
    session: {
        id: number;
        status: string;
        opening_amount: number;
        expected_cash: number | null;
        counted_cash: number | null;
        notes: string | null;
        opened_at: string;
        closed_at: string | null;
        register: Option;
        opener: { name: string } | null;
        closer: { name: string } | null;
    };
    summary: {
        by_method: Record<string, { count: number; total: number }>;
        total_collected: number;
        cash_in: number;
        cash_out: number;
        cancelled: number;
        expected_cash: number;
    };
    payments: { id: number; folio: string; method: string; amount: number; paid_at: string; cancelled_at: string | null; payer_info: PayerInfo | null }[];
    expenses: { id: number; description: string; amount: number; method: string; paid_at: string }[];
}>();

const isOpen = computed(() => props.session.status === 'abierta');
const print = (): void => window.print();
const form = useForm({ counted_cash: null as number | null, notes: '' });

const difference = computed(() => {
    const counted = isOpen.value ? form.counted_cash : props.session.counted_cash;
    const expected = isOpen.value ? props.summary.expected_cash : props.session.expected_cash;

    return counted === null || counted === undefined || String(counted) === '' || expected === null ? null : Number(counted) - expected;
});

const close = (): void => {
    Swal.fire({
        title: '¿Hacer el corte y cerrar la caja?',
        text: 'Ya no se podrán registrar ni cancelar pagos de este turno.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, cerrar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        confirmButtonColor: '#941B2B',
        cancelButtonColor: '#737982',
    }).then((result) => {
        if (result.isConfirmed) {
            form.transform((data) => ({ ...data, notes: data.notes || null })).patch(admin.finance.cashSessions.close(props.session.id).url, { preserveScroll: true });
        }
    });
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Caja y cortes', href: admin.finance.cashRegisters.index() },
            { title: 'Turno', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Corte · ${session.register.name}`" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            :title="`${session.register.name} · turno #${session.id}`"
            :description="`Abierta ${formatDateTime(session.opened_at)}${session.opener ? ` por ${session.opener.name}` : ''}${session.closed_at ? ` · cerrada ${formatDateTime(session.closed_at)}` : ''}`"
            section="Corte de caja"
            :icon="Wallet"
            :action-icon="ArrowLeft"
            action-label="Volver"
            :action-href="admin.finance.cashRegisters.index().url"
        >
            <template #details>
                <div class="mt-2"><StatusBadge :value="session.status" /></div>
            </template>
        </PageHeader>

        <div class="print:hidden">
            <AdminButton variant="secondary" :icon="Printer" @click="print">Imprimir corte</AdminButton>
        </div>

        <div class="grid gap-5 xl:grid-cols-3">
            <AdminCard title="Resumen" :icon="Wallet" class="xl:col-span-2">
                <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div>
                        <dt class="text-xs text-[#737982]">Fondo inicial</dt>
                        <dd class="text-lg font-bold text-[#292D32]">{{ formatMoney(session.opening_amount) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-[#737982]">Cobrado (todas las formas)</dt>
                        <dd class="text-lg font-bold text-[#187744]">{{ formatMoney(summary.total_collected) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-[#737982]">Gastos en efectivo</dt>
                        <dd class="text-lg font-bold text-[#941B2B]">{{ formatMoney(summary.cash_out) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-[#737982]">Efectivo esperado</dt>
                        <dd class="text-lg font-bold text-[#292D32]">{{ formatMoney(isOpen ? summary.expected_cash : session.expected_cash) }}</dd>
                    </div>
                </dl>

                <table class="mt-5 w-full text-sm">
                    <thead class="text-xs uppercase text-[#737982]">
                        <tr>
                            <th class="py-1 text-left">Forma de pago</th>
                            <th class="py-1 text-right">Pagos</th>
                            <th class="py-1 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E3E5E8]">
                        <tr v-for="(row, method) in summary.by_method" :key="method">
                            <td class="py-2">{{ label(String(method)) }}</td>
                            <td class="py-2 text-right">{{ row.count }}</td>
                            <td class="py-2 text-right font-semibold">{{ formatMoney(row.total) }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="summary.cancelled" class="mt-2 text-xs text-[#858B93]">{{ summary.cancelled }} pago(s) cancelado(s) no se cuentan.</p>
            </AdminCard>

            <AdminCard title="Corte" :icon="Lock">
                <template v-if="isOpen">
                    <FormInput id="cs_counted" v-model="form.counted_cash" type="number" step="0.01" min="0" label="Efectivo contado" :error="form.errors.counted_cash" required />
                    <div class="mt-3">
                        <FormTextarea id="cs_notes" v-model="form.notes" label="Notas" :rows="2" />
                    </div>
                </template>
                <dl v-else class="grid grid-cols-1 gap-2 text-sm">
                    <div class="flex justify-between"><dt class="text-[#737982]">Contado</dt><dd class="font-semibold">{{ formatMoney(session.counted_cash) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-[#737982]">Cerró</dt><dd>{{ session.closer?.name }}</dd></div>
                    <p v-if="session.notes" class="whitespace-pre-line text-[#525960]">{{ session.notes }}</p>
                </dl>

                <p v-if="difference !== null" :class="['mt-3 text-sm font-semibold', difference === 0 ? 'text-[#187744]' : difference < 0 ? 'text-[#941B2B]' : 'text-[#A64B00]']">
                    {{ difference === 0 ? 'Cuadra exacto' : `${difference < 0 ? 'Faltante' : 'Sobrante'}: ${formatMoney(Math.abs(difference))}` }}
                </p>

                <template v-if="isOpen" #footer>
                    <AdminButton :icon="Lock" :loading="form.processing" @click="close">Hacer corte y cerrar</AdminButton>
                </template>
            </AdminCard>
        </div>

        <AdminCard title="Pagos del turno" :padding="false">
            <ul v-if="payments.length" class="divide-y divide-[#E3E5E8]">
                <li v-for="p in payments" :key="p.id">
                    <Link :href="admin.finance.payments.show(p.id).url" :class="['flex items-center justify-between gap-3 px-5 py-3 hover:bg-[#FAF3F4] sm:px-6', p.cancelled_at ? 'opacity-50 line-through' : '']">
                        <span class="text-sm"><span class="font-semibold">{{ p.folio }}</span> · {{ p.payer_info?.name }} · {{ label(p.method) }}</span>
                        <span class="text-sm font-semibold">{{ formatMoney(p.amount) }}</span>
                    </Link>
                </li>
            </ul>
            <p v-else class="px-5 py-4 text-sm italic text-[#858B93]">Sin pagos en este turno.</p>
        </AdminCard>

        <AdminCard v-if="expenses.length" title="Gastos pagados desde la caja" :padding="false">
            <ul class="divide-y divide-[#E3E5E8]">
                <li v-for="e in expenses" :key="e.id" class="flex items-center justify-between gap-3 px-5 py-3 text-sm sm:px-6">
                    <span>{{ e.description }} · {{ formatDateTime(e.paid_at) }}</span>
                    <span class="font-semibold text-[#941B2B]">−{{ formatMoney(e.amount) }}</span>
                </li>
            </ul>
        </AdminCard>
    </div>
</template>
