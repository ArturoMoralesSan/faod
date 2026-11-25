<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Tag, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

import ActiveSwitch from '@/components/admin/ActiveSwitch.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { confirmDelete, enumOptions, formatMoney, label } from '@/lib/admin';
import admin from '@/routes/admin';

interface Concept {
    id: number;
    code: string;
    name: string;
    category: string;
    amount: number | null;
    is_active: boolean;
    charges_count: number;
}

defineProps<{
    concepts: Concept[];
    categories: string[];
}>();

const dialogOpen = ref(false);
const editing = ref<Concept | null>(null);
const form = useForm({ code: '', name: '', category: 'colegiatura', amount: null as number | null, is_active: true });

const open = (concept: Concept | null = null): void => {
    editing.value = concept;
    form.reset();
    form.clearErrors();

    if (concept) {
        Object.assign(form, { code: concept.code, name: concept.name, category: concept.category, amount: concept.amount, is_active: concept.is_active });
    }

    dialogOpen.value = true;
};

const submit = (): void => {
    const options = { preserveScroll: true, onSuccess: () => (dialogOpen.value = false) };

    form.transform((data) => ({ ...data, amount: data.amount === null || String(data.amount) === '' ? null : data.amount }));

    if (editing.value) {
        form.put(admin.finance.concepts.update(editing.value.id).url, options);
    } else {
        form.post(admin.finance.concepts.store().url, options);
    }
};

const destroy = (concept: Concept): void => {
    confirmDelete('¿Eliminar concepto?', `Se eliminará "${concept.name}".`, admin.finance.concepts.destroy(concept.id).url, { preserveState: true });
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Conceptos y cuotas', href: admin.finance.concepts.index() },
        ],
    },
});
</script>

<template>
    <Head title="Conceptos y cuotas" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader title="Conceptos y cuotas" description="Lo que se cobra y su monto de referencia." section="Finanzas" :icon="Tag" :action-icon="Plus" action-label="Nuevo concepto" @action="open()" />

        <AdminCard :padding="false">
            <ul v-if="concepts.length" class="divide-y divide-[#E3E5E8]">
                <li v-for="c in concepts" :key="c.id" class="flex items-center justify-between gap-3 px-5 py-3 sm:px-6">
                    <div>
                        <p class="text-sm font-semibold text-[#292D32]"><span class="text-[#941B2B]">{{ c.code }}</span> · {{ c.name }}</p>
                        <p class="text-xs text-[#858B93]">{{ label(c.category) }} · {{ c.amount !== null ? formatMoney(c.amount) : 'Monto variable' }} · {{ c.charges_count }} adeudo(s)</p>
                    </div>
                    <div class="flex items-center gap-1">
                        <StatusBadge :value="c.is_active" />
                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#383D43] hover:bg-[#F1F2F4]" title="Editar" @click="open(c)">
                            <Pencil class="h-4 w-4" />
                        </button>
                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F1F2F4]" title="Eliminar" @click="destroy(c)">
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </li>
            </ul>
            <EmptyState v-else :icon="Tag" title="Sin conceptos" description="Registra colegiaturas, inscripciones, constancias o tratamientos." />
        </AdminCard>

        <FormDialog v-model:open="dialogOpen" :title="editing ? 'Editar concepto' : 'Nuevo concepto'" :loading="form.processing" @submit="submit">
            <FormInput id="fc_code" v-model="form.code" label="Clave" placeholder="Ej. COL-SEM" :error="form.errors.code" required />
            <FormSelect id="fc_cat" v-model="form.category" label="Tipo" :options="enumOptions(categories)" :error="form.errors.category" required />
            <div class="md:col-span-2">
                <FormInput id="fc_name" v-model="form.name" label="Nombre" :error="form.errors.name" required />
            </div>
            <FormInput id="fc_amount" v-model="form.amount" type="number" step="0.01" min="0" label="Monto" help="Vacío si varía en cada cobro." :error="form.errors.amount" />
            <div />
            <div class="md:col-span-2">
                <ActiveSwitch v-model="form.is_active" label="Concepto activo" />
            </div>
        </FormDialog>
    </div>
</template>
