<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Trash2, Truck } from 'lucide-vue-next';
import { ref } from 'vue';

import ActiveSwitch from '@/components/admin/ActiveSwitch.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { confirmDelete } from '@/lib/admin';
import admin from '@/routes/admin';

interface Supplier {
    id: number;
    name: string;
    rfc: string | null;
    contact_name: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    notes: string | null;
    is_active: boolean;
    lots_count: number;
}

const props = defineProps<{
    suppliers: Supplier[];
    filters: { search?: string };
}>();

const search = ref(props.filters.search ?? '');
const submitSearch = (): void => {
    router.get(admin.suppliers.index().url, { search: search.value.trim() || undefined }, { preserveState: true, replace: true });
};

const dialogOpen = ref(false);
const editing = ref<Supplier | null>(null);
const empty = { name: '', rfc: '', contact_name: '', phone: '', email: '', address: '', notes: '', is_active: true };
const form = useForm({ ...empty });

const open = (supplier: Supplier | null = null): void => {
    editing.value = supplier;
    form.defaults({ ...empty });
    form.reset();
    form.clearErrors();

    if (supplier) {
        Object.assign(form, Object.fromEntries(Object.keys(empty).map((key) => [key, (supplier as unknown as Record<string, unknown>)[key] ?? (key === 'is_active' ? true : '')])));
    }

    dialogOpen.value = true;
};

const submit = (): void => {
    const options = { preserveScroll: true, onSuccess: () => (dialogOpen.value = false) };

    form.transform((data) => Object.fromEntries(Object.entries(data).map(([k, v]) => [k, v === '' ? null : v])));

    if (editing.value) {
        form.put(admin.suppliers.update(editing.value.id).url, options);
    } else {
        form.post(admin.suppliers.store().url, options);
    }
};

const destroy = (supplier: Supplier): void => {
    confirmDelete('¿Eliminar proveedor?', `Se eliminará "${supplier.name}".`, admin.suppliers.destroy(supplier.id).url, { preserveState: true });
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Proveedores', href: admin.suppliers.index() },
        ],
    },
});
</script>

<template>
    <Head title="Proveedores" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader title="Proveedores" description="Proveedores de material e insumos." section="Inventario" :icon="Truck" :action-icon="Plus" action-label="Nuevo proveedor" @action="open()" />

        <form class="relative max-w-md" @submit.prevent="submitSearch">
            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#858B93]" />
            <input v-model="search" type="search" placeholder="Buscar proveedor..." class="form-input pl-10" />
        </form>

        <AdminCard :padding="false">
            <ul v-if="suppliers.length" class="divide-y divide-[#E3E5E8]">
                <li v-for="s in suppliers" :key="s.id" class="flex items-center justify-between gap-3 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-[#292D32]">{{ s.name }}</p>
                        <p class="text-xs text-[#858B93]">{{ [s.rfc, s.contact_name, s.phone, s.email].filter(Boolean).join(' · ') || 'Sin datos de contacto' }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        <StatusBadge :value="s.is_active" />
                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#383D43] hover:bg-[#F9ECEE]" title="Editar" @click="open(s)">
                            <Pencil class="h-4 w-4" />
                        </button>
                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F9ECEE]" title="Eliminar" @click="destroy(s)">
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </li>
            </ul>
            <EmptyState v-else :icon="Truck" title="Sin proveedores" description="Registra el primer proveedor." />
        </AdminCard>

        <FormDialog v-model:open="dialogOpen" :title="editing ? 'Editar proveedor' : 'Nuevo proveedor'" :loading="form.processing" @submit="submit">
            <div class="md:col-span-2">
                <FormInput id="sp_name" v-model="form.name" label="Nombre o razón social" :error="form.errors.name" required />
            </div>
            <FormInput id="sp_rfc" v-model="form.rfc" label="RFC" :maxlength="13" :error="form.errors.rfc" />
            <FormInput id="sp_contact" v-model="form.contact_name" label="Contacto" :error="form.errors.contact_name" />
            <FormInput id="sp_phone" v-model="form.phone" type="tel" label="Teléfono" :error="form.errors.phone" />
            <FormInput id="sp_email" v-model="form.email" type="email" label="Correo" :error="form.errors.email" />
            <div class="md:col-span-2">
                <FormInput id="sp_address" v-model="form.address" label="Domicilio" :error="form.errors.address" />
            </div>
            <div class="md:col-span-2">
                <FormTextarea id="sp_notes" v-model="form.notes" label="Notas" :rows="2" />
            </div>
            <div class="md:col-span-2">
                <ActiveSwitch v-model="form.is_active" label="Proveedor activo" />
            </div>
        </FormDialog>
    </div>
</template>
