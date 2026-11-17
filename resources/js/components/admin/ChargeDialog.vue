<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import { formatMoney } from '@/lib/admin';
import admin from '@/routes/admin';
import type { ChargeData } from '@/types/admin';

/**
 * Descuento o cancelación de un adeudo.
 */
const props = defineProps<{
    charge: ChargeData | null;
    mode: 'discount' | 'cancel';
}>();

const open = defineModel<boolean>('open', { required: true });

const discountForm = useForm({ discount_amount: 0 as number | null, discount_reason: '' });
const cancelForm = useForm({ cancellation_reason: '' });

watch(open, (isOpen) => {
    if (isOpen && props.charge) {
        discountForm.clearErrors();
        cancelForm.reset();
        cancelForm.clearErrors();
        discountForm.discount_amount = props.charge.discount_amount;
        discountForm.discount_reason = props.charge.discount_reason ?? '';
    }
});

const submit = (): void => {
    if (!props.charge) {
        return;
    }

    const options = { preserveScroll: true, onSuccess: () => (open.value = false) };

    if (props.mode === 'discount') {
        discountForm.patch(admin.finance.charges.discount(props.charge.id).url, options);
    } else {
        cancelForm.patch(admin.finance.charges.cancel(props.charge.id).url, options);
    }
};
</script>

<template>
    <FormDialog
        v-model:open="open"
        :title="mode === 'discount' ? 'Descuento o beca' : 'Cancelar adeudo'"
        :description="charge ? `${charge.description} · ${formatMoney(charge.amount)}` : undefined"
        :submit-label="mode === 'discount' ? 'Aplicar descuento' : 'Cancelar adeudo'"
        :loading="discountForm.processing || cancelForm.processing"
        @submit="submit"
    >
        <template v-if="mode === 'discount'">
            <FormInput
                id="ch_discount"
                v-model="discountForm.discount_amount"
                type="number"
                step="0.01"
                min="0"
                label="Monto del descuento"
                :help="charge ? `Máximo ${formatMoney(charge.amount - charge.paid_amount)}` : ''"
                :error="discountForm.errors.discount_amount"
                required
            />
            <FormInput id="ch_reason" v-model="discountForm.discount_reason" label="Motivo" placeholder="Beca, convenio, cortesía…" :error="discountForm.errors.discount_reason" required />
        </template>
        <div v-else class="md:col-span-2">
            <FormInput id="ch_cancel" v-model="cancelForm.cancellation_reason" label="Motivo de la cancelación" :error="cancelForm.errors.cancellation_reason" required />
        </div>
    </FormDialog>
</template>
