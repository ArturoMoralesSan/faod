<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ClipboardPlus, Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import ActiveSwitch from '@/components/admin/ActiveSwitch.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { confirmDelete, formatMoney } from '@/lib/admin';
import admin from '@/routes/admin';

interface Procedure {
    id: number;
    code: string;
    name: string;
    category: string | null;
    default_cost: number | null;
    is_active: boolean;
    treatment_procedures_count: number;
}

const props = defineProps<{
    procedures: Procedure[];
    categories: string[];
    filters: { search?: string };
}>();

const search = ref(props.filters.search ?? '');

const submitSearch = (): void => {
    router.get(admin.procedures.index().url, { search: search.value.trim() || undefined }, { preserveState: true, replace: true });
};

const grouped = computed(() => {
    const groups = new Map<string, Procedure[]>();

    props.procedures.forEach((p) => {
        const key = p.category ?? 'Sin categoría';
        groups.set(key, [...(groups.get(key) ?? []), p]);
    });

    return [...groups.entries()];
});

const dialogOpen = ref(false);
const editing = ref<Procedure | null>(null);

const form = useForm({ code: '', name: '', category: '', default_cost: null as number | null, is_active: true });

const open = (procedure: Procedure | null = null): void => {
    editing.value = procedure;
    form.reset();
    form.clearErrors();

    if (procedure) {
        form.code = procedure.code;
        form.name = procedure.name;
        form.category = procedure.category ?? '';
        form.default_cost = procedure.default_cost;
        form.is_active = procedure.is_active;
    }

    dialogOpen.value = true;
};

const submit = (): void => {
    const options = { preserveScroll: true, onSuccess: () => (dialogOpen.value = false) };

    form.transform((data) => ({
        ...data,
        category: data.category || null,
        default_cost: data.default_cost === null || String(data.default_cost) === '' ? null : data.default_cost,
    }));

    if (editing.value) {
        form.put(admin.procedures.update(editing.value.id).url, options);
    } else {
        form.post(admin.procedures.store().url, options);
    }
};

const destroy = (procedure: Procedure): void => {
    confirmDelete(
        '¿Eliminar procedimiento?',
        `Se eliminará "${procedure.code} ${procedure.name}" del catálogo.`,
        admin.procedures.destroy(procedure.id).url,
        { preserveState: true },
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Catálogo de procedimientos', href: admin.procedures.index() },
        ],
    },
});
</script>

<template>
    <Head title="Catálogo de procedimientos" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Catálogo de procedimientos"
            description="Procedimientos que se agregan a los planes de tratamiento, con su costo sugerido."
            section="Atención clínica"
            :icon="ClipboardPlus"
            :action-icon="Plus"
            action-label="Nuevo procedimiento"
            @action="open()"
        />

        <form class="relative max-w-md" @submit.prevent="submitSearch">
            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#858B93]" />
            <input v-model="search" type="search" placeholder="Buscar por código o nombre..." class="form-input pl-10" />
        </form>

        <AdminCard v-for="[category, items] in grouped" :key="category" :title="category" :padding="false">
            <ul class="divide-y divide-[#E3E5E8]">
                <li v-for="p in items" :key="p.id" class="flex items-center justify-between gap-3 px-5 py-3 sm:px-6">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-[#292D32]"><span class="text-[#941B2B]">{{ p.code }}</span> · {{ p.name }}</p>
                        <p class="text-xs text-[#858B93]">
                            {{ p.default_cost !== null ? formatMoney(p.default_cost) : 'Sin costo sugerido' }} · usado {{ p.treatment_procedures_count }} vez/veces
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        <StatusBadge :value="p.is_active" />
                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#383D43] hover:bg-[#F9ECEE]" title="Editar" @click="open(p)">
                            <Pencil class="h-4 w-4" />
                        </button>
                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F9ECEE]" title="Eliminar" @click="destroy(p)">
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </li>
            </ul>
        </AdminCard>

        <AdminCard v-if="!procedures.length">
            <EmptyState :icon="ClipboardPlus" title="Sin procedimientos" description="No hay procedimientos que coincidan." />
        </AdminCard>

        <FormDialog v-model:open="dialogOpen" :title="editing ? 'Editar procedimiento' : 'Nuevo procedimiento'" :loading="form.processing" @submit="submit">
            <FormInput id="pr_code" v-model="form.code" label="Código" placeholder="Ej. OP-01" :error="form.errors.code" required />
            <FormInput
                id="pr_category"
                v-model="form.category"
                label="Categoría"
                :placeholder="categories.length ? `Ej. ${categories.slice(0, 3).join(', ')}` : 'Ej. Operatoria'"
                :error="form.errors.category"
            />
            <div class="md:col-span-2">
                <FormInput id="pr_name" v-model="form.name" label="Nombre" :error="form.errors.name" required />
            </div>
            <FormInput id="pr_cost" v-model="form.default_cost" type="number" step="0.01" min="0" label="Costo sugerido" :error="form.errors.default_cost" />
            <div />
            <div class="md:col-span-2">
                <ActiveSwitch v-model="form.is_active" label="Disponible en los planes" />
            </div>
        </FormDialog>
    </div>
</template>
