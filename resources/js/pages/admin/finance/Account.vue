<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { BadgePercent, Ban, Banknote, ExternalLink, Plus, Receipt, Wallet } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import AdminButton from '@/components/admin/AdminButton.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import ChargeDialog from '@/components/admin/ChargeDialog.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { enumOptions, formatDate, formatDateTime, formatMoney, label, recordOptions, todayInput } from '@/lib/admin';
import admin from '@/routes/admin';
import type { ChargeData, Option, SelectOption } from '@/types/admin';

interface PaymentRow {
    id: number;
    folio: string;
    method: string;
    amount: number;
    reference: string | null;
    paid_at: string;
    cancelled_at: string | null;
    cancellation_reason: string | null;
    receiver: { name: string } | null;
    allocations: { id: number; amount: number; charge: { id: number; description: string } }[];
}

const props = defineProps<{
    payer: { type: 'student' | 'patient'; id: number; name: string; code: string; phone: string | null; email: string | null; profile_url: string };
    charges: ChargeData[];
    payments: PaymentRow[];
    totals: { charged: number; paid: number; balance: number; overdue: number };
    openSessions: { id: number; opened_at: string; register: Option }[];
    concepts: (Option & { amount: number | null })[];
    methods: string[];
}>();

const today = todayInput();
const openCharges = computed(() => props.charges.filter((c) => ['pendiente', 'parcial'].includes(c.status)));
const sessionOptions = computed<SelectOption[]>(() =>
    props.openSessions.map((s) => ({ value: s.id, label: `${s.register.name} · abierta ${formatDateTime(s.opened_at)}` })),
);

/* ---------------------------------- Pago ---------------------------------- */

const payOpen = ref(false);
const payForm = useForm({
    method: 'efectivo',
    amount: null as number | null,
    cash_register_session_id: null as number | null,
    reference: '',
    notes: '',
    allocations: {} as Record<number, number | string | null>,
});

const allocatedTotal = computed(() =>
    Object.values(payForm.allocations).reduce<number>((sum, v) => sum + (Number(v) || 0), 0),
);

const openPay = (): void => {
    payForm.reset();
    payForm.clearErrors();
    payForm.allocations = Object.fromEntries(openCharges.value.map((c) => [c.id, c.balance]));
    payForm.amount = Math.round(allocatedTotal.value * 100) / 100;
    payForm.cash_register_session_id = props.openSessions.length === 1 ? props.openSessions[0].id : null;
    payOpen.value = true;
};

// El monto sigue la suma aplicada.
watch(allocatedTotal, (total) => {
    if (payOpen.value) {
        payForm.amount = Math.round(total * 100) / 100;
    }
});

const submitPay = (): void => {
    payForm.transform((data) => ({
        ...data,
        reference: data.reference || null,
        notes: data.notes || null,
        cash_register_session_id: data.method === 'efectivo' ? data.cash_register_session_id : data.cash_register_session_id || null,
        allocations: Object.fromEntries(Object.entries(data.allocations).filter(([, v]) => Number(v) > 0)),
    })).post(admin.finance.payments.store({ type: props.payer.type, id: props.payer.id }).url);
};

/* --------------------------------- Adeudo --------------------------------- */

const chargeOpen = ref(false);
const chargeForm = useForm({
    payer_type: props.payer.type,
    payer_id: props.payer.id,
    fee_concept_id: null as number | null,
    description: '',
    amount: null as number | null,
    due_date: '',
});

const onConcept = (): void => {
    const concept = props.concepts.find((c) => c.id === chargeForm.fee_concept_id);

    if (concept) {
        chargeForm.description = chargeForm.description || concept.name;
        chargeForm.amount = chargeForm.amount ?? concept.amount;
    }
};

const submitCharge = (): void => {
    chargeForm.transform((data) => ({ ...data, due_date: data.due_date || null }))
        .post(admin.finance.charges.store().url, { preserveScroll: true, onSuccess: () => { chargeOpen.value = false; chargeForm.reset(); } });
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

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Adeudos', href: admin.finance.charges.index() },
            { title: 'Estado de cuenta', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Cuenta · ${payer.name}`" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader :title="payer.name" :description="`${label(payer.type)} · ${payer.code}`" section="Estado de cuenta" :icon="Wallet">
            <template #details>
                <Link :href="payer.profile_url" class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-[#941B2B] hover:underline">
                    <ExternalLink class="h-4 w-4" /> {{ payer.type === 'patient' ? 'Ver expediente' : 'Ver datos personales' }}
                </Link>
            </template>
        </PageHeader>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-2xl border border-[#E3E5E8] bg-white p-4">
                <p class="text-xs text-[#737982]">Cargado</p>
                <p class="text-xl font-bold text-[#292D32]">{{ formatMoney(totals.charged) }}</p>
            </div>
            <div class="rounded-2xl border border-[#E3E5E8] bg-white p-4">
                <p class="text-xs text-[#737982]">Pagado</p>
                <p class="text-xl font-bold text-[#187744]">{{ formatMoney(totals.paid) }}</p>
            </div>
            <div class="rounded-2xl border border-[#E3E5E8] bg-white p-4">
                <p class="text-xs text-[#737982]">Saldo</p>
                <p class="text-xl font-bold text-[#292D32]">{{ formatMoney(totals.balance) }}</p>
            </div>
            <div class="rounded-2xl border border-[#E8C7CC] bg-[#F9ECEE] p-4">
                <p class="text-xs text-[#941B2B]">Vencido</p>
                <p class="text-xl font-bold text-[#941B2B]">{{ formatMoney(totals.overdue) }}</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <AdminButton :icon="Banknote" :disabled="!openCharges.length" @click="openPay">Registrar pago</AdminButton>
            <AdminButton variant="secondary" :icon="Plus" @click="chargeOpen = true">Agregar adeudo</AdminButton>
        </div>

        <AdminCard title="Adeudos" :icon="Receipt" :padding="false">
            <table v-if="charges.length" class="w-full text-left text-sm">
                <thead class="bg-[#F7F8FA] text-xs font-semibold uppercase tracking-wide text-[#383D43]">
                    <tr>
                        <th class="px-4 py-3">Concepto</th>
                        <th class="px-4 py-3">Vence</th>
                        <th class="px-4 py-3 text-right">Monto</th>
                        <th class="px-4 py-3 text-right">Pagado</th>
                        <th class="px-4 py-3 text-right">Saldo</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E3E5E8]">
                    <tr v-for="c in charges" :key="c.id" :class="c.status === 'cancelado' ? 'opacity-50' : ''">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-[#292D32]">{{ c.description }}</p>
                            <p class="text-xs text-[#858B93]">
                                {{ [c.concept?.code, c.academic_period?.name, c.discount_amount ? `Descuento ${formatMoney(c.discount_amount)}${c.discount_reason ? ` (${c.discount_reason})` : ''}` : null, c.cancellation_reason].filter(Boolean).join(' · ') }}
                            </p>
                        </td>
                        <td :class="['px-4 py-3', c.due_date && c.due_date < today && c.balance > 0 ? 'font-semibold text-[#941B2B]' : 'text-[#525960]']">{{ c.due_date ? formatDate(c.due_date) : '—' }}</td>
                        <td class="px-4 py-3 text-right text-[#525960]">{{ formatMoney(c.amount) }}</td>
                        <td class="px-4 py-3 text-right text-[#187744]">{{ formatMoney(c.paid_amount) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-[#292D32]">{{ formatMoney(c.balance) }}</td>
                        <td class="px-4 py-3"><StatusBadge :value="c.status" /></td>
                        <td class="px-4 py-3">
                            <div v-if="['pendiente', 'parcial'].includes(c.status)" class="flex justify-end gap-1">
                                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#383D43] hover:bg-[#F9ECEE]" title="Descuento" @click="openAction(c, 'discount')">
                                    <BadgePercent class="h-4 w-4" />
                                </button>
                                <button v-if="!c.paid_amount" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F9ECEE]" title="Cancelar" @click="openAction(c, 'cancel')">
                                    <Ban class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p v-else class="px-5 py-4 text-sm italic text-[#858B93]">Sin adeudos.</p>
        </AdminCard>

        <AdminCard title="Pagos" :icon="Banknote" :padding="false">
            <ul v-if="payments.length" class="divide-y divide-[#E3E5E8]">
                <li v-for="p in payments" :key="p.id">
                    <Link :href="admin.finance.payments.show(p.id).url" :class="['flex items-center justify-between gap-3 px-5 py-3 hover:bg-[#FAF3F4] sm:px-6', p.cancelled_at ? 'opacity-60' : '']">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-[#292D32]">{{ p.folio }} · {{ formatMoney(p.amount) }} · {{ label(p.method) }}</p>
                            <p class="truncate text-xs text-[#858B93]">
                                {{ formatDateTime(p.paid_at) }} · {{ p.allocations.map((a) => a.charge.description).join(', ') }}
                                <template v-if="p.cancelled_at"> · CANCELADO: {{ p.cancellation_reason }}</template>
                            </p>
                        </div>
                        <StatusBadge :value="p.cancelled_at ? 'cancelado' : 'pagado'" />
                    </Link>
                </li>
            </ul>
            <p v-else class="px-5 py-4 text-sm italic text-[#858B93]">Sin pagos.</p>
        </AdminCard>

        <!-- Registrar pago -->
        <FormDialog v-model:open="payOpen" title="Registrar pago" submit-label="Cobrar" :loading="payForm.processing" @submit="submitPay">
            <div class="md:col-span-2">
                <p class="form-label mb-2">Aplicar a</p>
                <ul class="divide-y divide-[#E3E5E8] rounded-xl border border-[#E3E5E8]">
                    <li v-for="c in openCharges" :key="c.id" class="flex items-center justify-between gap-3 px-3 py-2">
                        <div class="min-w-0 text-sm">
                            <p class="truncate font-semibold text-[#292D32]">{{ c.description }}</p>
                            <p class="text-xs text-[#858B93]">Saldo {{ formatMoney(c.balance) }}</p>
                        </div>
                        <input v-model="payForm.allocations[c.id]" type="number" step="0.01" min="0" :max="c.balance" class="form-input h-9 w-32 py-1 text-right text-sm" />
                    </li>
                </ul>
                <p v-if="payForm.errors.allocations" class="form-error">{{ payForm.errors.allocations }}</p>
            </div>
            <FormSelect id="pay_method" v-model="payForm.method" label="Forma de pago" :options="enumOptions(methods)" :error="payForm.errors.method" required />
            <FormInput id="pay_amount" v-model="payForm.amount" type="number" step="0.01" min="0.01" label="Monto" :error="payForm.errors.amount" required />
            <div class="md:col-span-2">
                <FormSelect
                    id="pay_session"
                    v-model="payForm.cash_register_session_id"
                    label="Caja"
                    :placeholder="payForm.method === 'efectivo' ? (sessionOptions.length ? 'Elige la caja' : 'No hay cajas abiertas') : 'Sin caja'"
                    :options="sessionOptions"
                    :error="payForm.errors.cash_register_session_id"
                    :required="payForm.method === 'efectivo'"
                    nullable
                />
            </div>
            <FormInput v-if="payForm.method !== 'efectivo'" id="pay_ref" v-model="payForm.reference" label="Referencia" placeholder="Folio de la transferencia o voucher" :error="payForm.errors.reference" :required="['transferencia', 'deposito'].includes(payForm.method)" />
            <div class="md:col-span-2">
                <FormTextarea id="pay_notes" v-model="payForm.notes" label="Notas" :rows="2" />
            </div>
        </FormDialog>

        <!-- Agregar adeudo -->
        <FormDialog v-model:open="chargeOpen" title="Agregar adeudo" :loading="chargeForm.processing" @submit="submitCharge">
            <div class="md:col-span-2" @change="onConcept">
                <FormSelect id="ac_concept" v-model="chargeForm.fee_concept_id" label="Concepto" placeholder="Sin concepto" :options="recordOptions(concepts, true)" nullable />
            </div>
            <div class="md:col-span-2">
                <FormInput id="ac_desc" v-model="chargeForm.description" label="Descripción" :error="chargeForm.errors.description" required />
            </div>
            <FormInput id="ac_amount" v-model="chargeForm.amount" type="number" step="0.01" min="0.01" label="Monto" :error="chargeForm.errors.amount" required />
            <FormInput id="ac_due" v-model="chargeForm.due_date" type="date" label="Vence" />
        </FormDialog>

        <ChargeDialog v-model:open="actionOpen" :charge="actionCharge" :mode="actionMode" />
    </div>
</template>
