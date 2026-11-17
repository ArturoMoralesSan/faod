<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Boxes, Pencil, Plus } from 'lucide-vue-next';
import { ref } from 'vue';

import ActiveSwitch from '@/components/admin/ActiveSwitch.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { recordOptions } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option } from '@/types/admin';

interface Warehouse {
    id: number;
    clinic_id: number | null;
    code: string;
    name: string;
    is_active: boolean;
    items_count: number;
    clinic: Option | null;
}

defineProps<{
    warehouses: Warehouse[];
    clinics: Option[];
}>();

const dialogOpen = ref(false);
const editing = ref<Warehouse | null>(null);
const form = useForm({ clinic_id: null as number | null, code: '', name: '', is_active: true });

const open = (warehouse: Warehouse | null = null): void => {
    editing.value = warehouse;
    form.reset();
    form.clearErrors();

    if (warehouse) {
        Object.assign(form, { clinic_id: warehouse.clinic_id, code: warehouse.code, name: warehouse.name, is_active: warehouse.is_active });
    }

    dialogOpen.value = true;
};

const submit = (): void => {
    const options = { preserveScroll: true, onSuccess: () => (dialogOpen.value = false) };

    if (editing.value) {
        form.put(admin.warehouses.update(editing.value.id).url, options);
    } else {
        form.post(admin.warehouses.store().url, options);
    }
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Almacenes', href: admin.warehouses.index() },
        ],
    },
});
</script>

<template>
    <Head title="Almacenes" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader title="Almacenes" description="Almacén general y almacenes de cada clínica." section="Inventario" :icon="Boxes" :action-icon="Plus" action-label="Nuevo almacén" @action="open()" />

        <AdminCard :padding="false">
            <ul v-if="warehouses.length" class="divide-y divide-[#E3E5E8]">
                <li v-for="w in warehouses" :key="w.id" class="flex items-center justify-between gap-3 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-sm font-semibold text-[#292D32]">{{ w.name }}</p>
                        <p class="text-xs text-[#858B93]">{{ w.code }}<template v-if="w.clinic"> · {{ w.clinic.name }}</template> · {{ w.items_count }} lote(s) con existencia</p>
                    </div>
                    <div class="flex items-center gap-1">
                        <StatusBadge :value="w.is_active" />
                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#383D43] hover:bg-[#F9ECEE]" title="Editar" @click="open(w)">
                            <Pencil class="h-4 w-4" />
                        </button>
                    </div>
                </li>
            </ul>
            <EmptyState v-else :icon="Boxes" title="Sin almacenes" description="Registra el primer almacén." />
        </AdminCard>

        <FormDialog v-model:open="dialogOpen" :title="editing ? 'Editar almacén' : 'Nuevo almacén'" :loading="form.processing" @submit="submit">
            <FormInput id="wh_code" v-model="form.code" label="Clave" :error="form.errors.code" required />
            <FormInput id="wh_name" v-model="form.name" label="Nombre" :error="form.errors.name" required />
            <div class="md:col-span-2">
                <FormSelect id="wh_clinic" v-model="form.clinic_id" label="Clínica" placeholder="Almacén general" :options="recordOptions(clinics)" nullable />
            </div>
            <div class="md:col-span-2">
                <ActiveSwitch v-model="form.is_active" label="Almacén activo" />
            </div>
        </FormDialog>
    </div>
</template>
