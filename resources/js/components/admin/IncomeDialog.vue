<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import { enumOptions, formatMoney } from '@/lib/admin';
import admin from '@/routes/admin';
import type { SelectOption } from '@/types/admin';

/**
 * Cobro manual que no salda un adeudo: inscripción a un curso, cobro a un externo,
 * donativo, trabajo de laboratorio… Sale con folio y entra al corte de caja.
 */
const props = defineProps<{
    methods: string[];
    openSessions: { id: number; register: { name: string } | null }[];
    feeConcepts?: { id: number; code: string; name: string; amount: number | null }[];
    /** Si se cobra la inscripción de un participante. */
    participant?: { id: number; name: string; activity: string; amount: number | null } | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const form = useForm({
    activity_participant_id: null as number | null,
    payer_name: '',
    fee_concept_id: null as number | null,
    concept: '',
    method: 'efectivo',
    amount: null as number | null,
    cash_register_session_id: null as number | null,
    reference: '',
    notes: '',
});

const sessionOptions = computed<SelectOption[]>(() => props.openSessions.map((s) => ({ value: s.id, label: s.register?.name ?? `Caja ${s.id}` })));
const conceptOptions = computed<SelectOption[]>(() =>
    (props.feeConcepts ?? []).map((c) => ({ value: c.id, label: `${c.name}${c.amount ? ` · ${formatMoney(c.amount)}` : ''}` })),
);

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    form.reset();
    form.clearErrors();
    form.activity_participant_id = props.participant?.id ?? null;
    form.amount = props.participant?.amount ?? null;
    form.cash_register_session_id = props.openSessions.length === 1 ? props.openSessions[0].id : null;
});

// Al elegir un concepto de cobro, propone su cuota.
watch(
    () => form.fee_concept_id,
    (id) => {
        const concept = props.feeConcepts?.find((c) => c.id === id);

        if (concept?.amount && !form.amount) {
            form.amount = concept.amount;
        }
    },
);

const submit = (): void => {
    form.transform((data) => Object.fromEntries(Object.entries(data).map(([k, v]) => [k, v === '' ? null : v])))
        .post(admin.finance.incomes.store().url, { preserveScroll: true, onSuccess: () => (open.value = false) });
};
</script>

<template>
    <FormDialog
        v-model:open="open"
        :title="participant ? 'Cobrar inscripción' : 'Registrar otro ingreso'"
        :description="participant ? `${participant.name} · ${participant.activity}` : 'Ingreso que no corresponde a un adeudo de alumno o paciente.'"
        :loading="form.processing"
        submit-label="Registrar cobro"
        @submit="submit"
    >
        <template v-if="!participant">
            <div class="md:col-span-2">
                <FormInput id="in_payer" v-model="form.payer_name" label="Quién paga" placeholder="Nombre de la persona o institución" :error="form.errors.payer_name" required />
            </div>
            <FormSelect
                v-if="conceptOptions.length"
                id="in_fee"
                v-model="form.fee_concept_id"
                label="Concepto de cobro"
                placeholder="Otro (escribirlo)"
                :options="conceptOptions"
                :error="form.errors.fee_concept_id"
                nullable
            />
            <FormInput
                id="in_concept"
                v-model="form.concept"
                label="Concepto"
                :placeholder="form.fee_concept_id ? 'Opcional: detalle' : 'Ej. Donativo, trabajo de laboratorio'"
                :error="form.errors.concept"
                :required="!form.fee_concept_id"
            />
        </template>

        <FormSelect id="in_method" v-model="form.method" label="Forma de pago" :options="enumOptions(methods)" :error="form.errors.method" required />
        <FormInput id="in_amount" v-model="form.amount" type="number" step="0.01" min="0.01" label="Monto" :error="form.errors.amount" required />
        <FormSelect
            id="in_session"
            v-model="form.cash_register_session_id"
            label="Caja"
            :placeholder="form.method === 'efectivo' ? 'Elige la caja abierta' : 'Sin caja'"
            :options="sessionOptions"
            :help="form.method === 'efectivo' && !openSessions.length ? 'No hay cajas abiertas: abre una en Caja y cortes.' : ''"
            :error="form.errors.cash_register_session_id"
            :required="form.method === 'efectivo'"
            nullable
        />
        <FormInput
            id="in_reference"
            v-model="form.reference"
            label="Referencia"
            placeholder="No. de autorización o rastreo"
            :error="form.errors.reference"
            :required="['transferencia', 'deposito'].includes(form.method)"
        />
        <div class="md:col-span-2">
            <FormTextarea id="in_notes" v-model="form.notes" label="Notas" :rows="2" :error="form.errors.notes" />
        </div>
        <p v-if="form.errors.activity_participant_id" class="md:col-span-2 text-sm text-[#941B2B]">{{ form.errors.activity_participant_id }}</p>
    </FormDialog>
</template>
