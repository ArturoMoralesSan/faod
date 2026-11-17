<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LockOpen, Plus, Wallet } from 'lucide-vue-next';
import { ref } from 'vue';

import AdminButton from '@/components/admin/AdminButton.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { formatDateTime, formatMoney, recordOptions } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option } from '@/types/admin';

interface Session {
    id: number;
    status: string;
    opening_amount: number;
    expected_cash: number | null;
    counted_cash: number | null;
    opened_at: string;
    closed_at: string | null;
    register?: Option;
    opener: { name: string } | null;
    closer?: { name: string } | null;
}

defineProps<{
    registers: { id: number; name: string; clinic: Option | null; open_session: Session | null }[];
    sessions: Session[];
    clinics: Option[];
}>();

const registerOpen = ref(false);
const registerForm = useForm({ name: '', clinic_id: null as number | null });

const submitRegister = (): void => {
    registerForm.post(admin.finance.cashRegisters.store().url, { preserveScroll: true, onSuccess: () => { registerOpen.value = false; registerForm.reset(); } });
};

const openingFor = ref<number | null>(null);
const openingOpen = ref(false);
const openingForm = useForm({ opening_amount: 0 as number | null });

const startOpening = (id: number): void => {
    openingFor.value = id;
    openingForm.reset();
    openingForm.clearErrors();
    openingOpen.value = true;
};

const submitOpening = (): void => {
    if (openingFor.value) {
        openingForm.post(admin.finance.cashRegisters.open(openingFor.value).url, { onSuccess: () => (openingOpen.value = false) });
    }
};

const difference = (s: Session) => (s.counted_cash !== null && s.expected_cash !== null ? s.counted_cash - s.expected_cash : null);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Caja y cortes', href: admin.finance.cashRegisters.index() },
        ],
    },
});
</script>

<template>
    <Head title="Caja y cortes" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader title="Caja y cortes" description="Apertura de caja con fondo y corte al cierre del turno." section="Finanzas" :icon="Wallet" :action-icon="Plus" action-label="Nueva caja" @action="registerOpen = true" />

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div v-for="r in registers" :key="r.id" class="rounded-2xl border border-[#E3E5E8] bg-white p-5">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-base font-bold text-[#292D32]">{{ r.name }}</p>
                        <p class="text-xs text-[#858B93]">{{ r.clinic?.name ?? 'General' }}</p>
                    </div>
                    <StatusBadge :value="r.open_session ? 'abierta' : 'cerrada'" />
                </div>
                <div class="mt-4">
                    <template v-if="r.open_session">
                        <p class="text-xs text-[#737982]">Abierta {{ formatDateTime(r.open_session.opened_at) }}<template v-if="r.open_session.opener"> por {{ r.open_session.opener.name }}</template></p>
                        <Link :href="admin.finance.cashSessions.show(r.open_session.id).url" class="mt-2 inline-block text-sm font-semibold text-[#941B2B] hover:underline">Ver turno y hacer corte</Link>
                    </template>
                    <AdminButton v-else :icon="LockOpen" @click="startOpening(r.id)">Abrir caja</AdminButton>
                </div>
            </div>
        </div>

        <AdminCard v-if="!registers.length">
            <EmptyState :icon="Wallet" title="Sin cajas" description="Registra la primera caja para recibir pagos en efectivo." />
        </AdminCard>

        <AdminCard title="Turnos recientes" :icon="Wallet" :padding="false">
            <ul v-if="sessions.length" class="divide-y divide-[#E3E5E8]">
                <li v-for="s in sessions" :key="s.id">
                    <Link :href="admin.finance.cashSessions.show(s.id).url" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-[#FAF3F4] sm:px-6">
                        <div>
                            <p class="text-sm font-semibold text-[#292D32]">{{ s.register?.name }} · {{ formatDateTime(s.opened_at) }}</p>
                            <p class="text-xs text-[#858B93]">
                                Fondo {{ formatMoney(s.opening_amount) }}
                                <template v-if="s.closed_at"> · cerrado {{ formatDateTime(s.closed_at) }} · esperado {{ formatMoney(s.expected_cash) }} · contado {{ formatMoney(s.counted_cash) }}</template>
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span v-if="difference(s) !== null && difference(s) !== 0" :class="['text-xs font-semibold', difference(s)! < 0 ? 'text-[#941B2B]' : 'text-[#187744]']">
                                {{ difference(s)! > 0 ? 'Sobrante' : 'Faltante' }} {{ formatMoney(Math.abs(difference(s)!)) }}
                            </span>
                            <StatusBadge :value="s.status" />
                        </div>
                    </Link>
                </li>
            </ul>
            <p v-else class="px-5 py-4 text-sm italic text-[#858B93]">Sin turnos.</p>
        </AdminCard>

        <FormDialog v-model:open="registerOpen" title="Nueva caja" :loading="registerForm.processing" @submit="submitRegister">
            <FormInput id="cr_name" v-model="registerForm.name" label="Nombre" placeholder="Ej. Caja recepción" :error="registerForm.errors.name" required />
            <FormSelect id="cr_clinic" v-model="registerForm.clinic_id" label="Clínica" placeholder="General" :options="recordOptions(clinics)" nullable />
        </FormDialog>

        <FormDialog v-model:open="openingOpen" title="Abrir caja" submit-label="Abrir caja" :loading="openingForm.processing" @submit="submitOpening">
            <FormInput id="op_amount" v-model="openingForm.opening_amount" type="number" step="0.01" min="0" label="Fondo inicial (efectivo)" :error="openingForm.errors.opening_amount" required />
        </FormDialog>
    </div>
</template>
