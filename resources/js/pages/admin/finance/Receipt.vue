<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Ban, Printer } from 'lucide-vue-next';
import { ref } from 'vue';

import AdminButton from '@/components/admin/AdminButton.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import { formatDateTime, formatMoney, label } from '@/lib/admin';
import admin from '@/routes/admin';
import type { PayerInfo } from '@/types/admin';

const props = defineProps<{
    payment: {
        id: number;
        folio: string;
        method: string;
        amount: number;
        reference: string | null;
        paid_at: string;
        notes: string | null;
        cancelled_at: string | null;
        cancellation_reason: string | null;
        payer_info: PayerInfo | null;
        concept: string | null;
        participant: { activity: { id: number; title: string } | null } | null;
        receiver: { name: string } | null;
        canceller: { name: string } | null;
        session: { register: { name: string } } | null;
        allocations: { id: number; amount: number; charge: { id: number; description: string } }[];
    };
}>();

const cancelOpen = ref(false);
const cancelForm = useForm({ cancellation_reason: '' });

const submitCancel = (): void => {
    cancelForm.patch(admin.finance.payments.cancel(props.payment.id).url, { preserveScroll: true, onSuccess: () => (cancelOpen.value = false) });
};

const print = (): void => window.print();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Pagos', href: admin.finance.payments.index() },
            { title: 'Recibo', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Recibo ${payment.folio}`" />

    <div class="flex h-full flex-1 flex-col gap-5 bg-[#F7F8FA] p-4 sm:p-6">
        <div class="flex flex-wrap gap-2 print:hidden">
            <Link v-if="payment.participant?.activity" :href="admin.activities.show(payment.participant.activity.id).url">
                <AdminButton variant="secondary" :icon="ArrowLeft">Volver a la actividad</AdminButton>
            </Link>
            <Link v-if="payment.payer_info?.url" :href="payment.payer_info.url">
                <AdminButton variant="secondary" :icon="ArrowLeft">Estado de cuenta</AdminButton>
            </Link>
            <AdminButton :icon="Printer" @click="print">Imprimir</AdminButton>
            <AdminButton v-if="!payment.cancelled_at" variant="secondary" :icon="Ban" @click="cancelOpen = true">Cancelar pago</AdminButton>
        </div>

        <article class="relative mx-auto w-full max-w-2xl rounded-2xl border border-[#E3E5E8] bg-white p-8 print:border-0 print:p-0">
            <div v-if="payment.cancelled_at" class="pointer-events-none absolute inset-0 flex items-center justify-center">
                <span class="-rotate-12 rounded-xl border-4 border-[#941B2B] px-6 py-2 text-4xl font-black text-[#941B2B] opacity-30">CANCELADO</span>
            </div>

            <header class="flex items-start justify-between gap-4 border-b border-[#E3E5E8] pb-5">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#941B2B]">Universidad Juárez del Estado de Durango</p>
                    <p class="text-sm font-semibold text-[#292D32]">Facultad de Odontología</p>
                    <p class="mt-1 text-xs text-[#737982]">Recibo de pago</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-[#737982]">Folio</p>
                    <p class="text-xl font-bold text-[#292D32]">{{ payment.folio }}</p>
                    <p class="text-xs text-[#737982]">{{ formatDateTime(payment.paid_at) }}</p>
                </div>
            </header>

            <section class="grid grid-cols-2 gap-4 py-5 text-sm">
                <div>
                    <p class="text-xs text-[#737982]">Recibimos de</p>
                    <p class="font-semibold text-[#292D32]">{{ payment.payer_info?.name }}</p>
                    <p class="text-xs text-[#737982]">{{ payment.payer_info ? [label(payment.payer_info.type), payment.payer_info.code].filter(Boolean).join(' · ') : '' }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-[#737982]">Forma de pago</p>
                    <p class="font-semibold text-[#292D32]">{{ label(payment.method) }}</p>
                    <p v-if="payment.reference" class="text-xs text-[#737982]">Ref. {{ payment.reference }}</p>
                </div>
            </section>

            <table class="w-full text-sm">
                <thead class="border-y border-[#E3E5E8] text-xs uppercase text-[#737982]">
                    <tr>
                        <th class="py-2 text-left">Concepto</th>
                        <th class="py-2 text-right">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="a in payment.allocations" :key="a.id" class="border-b border-[#F1F2F4]">
                        <td class="py-2 text-[#292D32]">{{ a.charge.description }}</td>
                        <td class="py-2 text-right text-[#292D32]">{{ formatMoney(a.amount) }}</td>
                    </tr>
                    <tr v-if="!payment.allocations.length" class="border-b border-[#F1F2F4]">
                        <td class="py-2 text-[#292D32]">{{ payment.concept ?? (payment.participant ? `Inscripción: ${payment.participant.activity?.title}` : 'Ingreso') }}</td>
                        <td class="py-2 text-right text-[#292D32]">{{ formatMoney(payment.amount) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td class="pt-3 text-right font-semibold text-[#292D32]">Total</td>
                        <td class="pt-3 text-right text-lg font-bold text-[#292D32]">{{ formatMoney(payment.amount) }}</td>
                    </tr>
                </tfoot>
            </table>

            <footer class="mt-8 flex items-end justify-between gap-4 text-xs text-[#737982]">
                <div>
                    <p v-if="payment.session">Caja: {{ payment.session.register.name }}</p>
                    <p v-if="payment.notes">{{ payment.notes }}</p>
                    <p v-if="payment.cancelled_at" class="font-semibold text-[#941B2B]">
                        Cancelado {{ formatDateTime(payment.cancelled_at) }}<template v-if="payment.canceller"> por {{ payment.canceller.name }}</template>: {{ payment.cancellation_reason }}
                    </p>
                </div>
                <div class="w-48 border-t border-[#292D32] pt-1 text-center">
                    Recibió: {{ payment.receiver?.name }}
                </div>
            </footer>
        </article>

        <FormDialog v-model:open="cancelOpen" title="Cancelar pago" description="El pago conserva su folio; sus montos se regresan a los adeudos." submit-label="Cancelar pago" :loading="cancelForm.processing" @submit="submitCancel">
            <div class="md:col-span-2">
                <FormInput id="cp_reason" v-model="cancelForm.cancellation_reason" label="Motivo" :error="cancelForm.errors.cancellation_reason || (cancelForm.errors as Record<string, string>).payment" required />
            </div>
        </FormDialog>
    </div>
</template>
