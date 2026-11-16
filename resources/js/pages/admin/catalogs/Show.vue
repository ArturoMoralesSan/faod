<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Hash, ListTree, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

import ActiveSwitch from '@/components/admin/ActiveSwitch.vue';
import AdminButton from '@/components/admin/AdminButton.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { confirmDelete } from '@/lib/admin';
import admin from '@/routes/admin';

interface CatalogItem {
    id: number;
    code: string;
    name: string;
    sort: number;
    is_active: boolean;
}

const props = defineProps<{
    catalog: {
        id: number;
        code: string;
        name: string;
        description: string | null;
        is_system: boolean;
        items: CatalogItem[];
    };
}>();

const dialogOpen = ref(false);
const editing = ref<CatalogItem | null>(null);

const form = useForm({
    code: '',
    name: '',
    sort: 0 as number | null,
    is_active: true,
});

const openCreate = (): void => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.sort = props.catalog.items.length;
    dialogOpen.value = true;
};

const openEdit = (item: CatalogItem): void => {
    editing.value = item;
    form.clearErrors();
    form.code = item.code;
    form.name = item.name;
    form.sort = item.sort;
    form.is_active = item.is_active;
    dialogOpen.value = true;
};

const submit = (): void => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
        },
    };

    if (editing.value) {
        form.put(admin.catalogItems.update(editing.value.id).url, options);
    } else {
        form.post(admin.catalogs.items.store(props.catalog.id).url, options);
    }
};

const destroy = (item: CatalogItem): void => {
    confirmDelete(
        '¿Eliminar opción?',
        `Se eliminará "${item.name}" del catálogo.`,
        admin.catalogItems.destroy(item.id).url,
        { preserveState: true },
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Catálogos', href: admin.catalogs.index() },
            { title: 'Detalle', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="catalog.name" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            :title="catalog.name"
            :description="catalog.description ?? `Código: ${catalog.code}`"
            section="Catálogos"
            :icon="ListTree"
            :action-icon="ArrowLeft"
            action-label="Volver a catálogos"
            :action-href="admin.catalogs.index().url"
        />

        <AdminCard
            title="Opciones"
            description="Las opciones inactivas no aparecen en los formularios."
            :icon="ListTree"
            :padding="false"
        >
            <div class="flex justify-end border-b border-[#E3E5E8] px-5 py-3 sm:px-6">
                <AdminButton :icon="Plus" @click="openCreate">Agregar opción</AdminButton>
            </div>

            <table v-if="catalog.items.length" class="w-full border-collapse text-left text-sm">
                <thead class="bg-[#F7F8FA] text-xs font-semibold uppercase tracking-wide text-[#383D43]">
                    <tr>
                        <th class="px-5 py-3 sm:px-6">Orden</th>
                        <th class="px-5 py-3 sm:px-6">Código</th>
                        <th class="px-5 py-3 sm:px-6">Nombre</th>
                        <th class="px-5 py-3 sm:px-6">Estado</th>
                        <th class="px-5 py-3 text-right sm:px-6">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-[#E3E5E8]">
                    <tr v-for="item in catalog.items" :key="item.id" class="hover:bg-[#FAF3F4]">
                        <td class="px-5 py-3 text-[#858B93] sm:px-6">{{ item.sort }}</td>
                        <td class="px-5 py-3 font-mono text-xs text-[#525960] sm:px-6">{{ item.code }}</td>
                        <td class="px-5 py-3 font-semibold text-[#292D32] sm:px-6">{{ item.name }}</td>
                        <td class="px-5 py-3 sm:px-6"><StatusBadge :value="item.is_active" /></td>
                        <td class="px-5 py-3 sm:px-6">
                            <div class="flex justify-end gap-1.5">
                                <button
                                    type="button"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-[#383D43] transition-colors hover:bg-[#F9ECEE] hover:text-[#941B2B]"
                                    title="Editar"
                                    @click="openEdit(item)"
                                >
                                    <Pencil class="h-4 w-4" />
                                </button>

                                <button
                                    type="button"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-[#941B2B] transition-colors hover:bg-[#F9ECEE]"
                                    title="Eliminar"
                                    @click="destroy(item)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <EmptyState
                v-else
                :icon="ListTree"
                title="Sin opciones"
                description="Agrega la primera opción de este catálogo."
            />
        </AdminCard>

        <FormDialog
            v-model:open="dialogOpen"
            :title="editing ? 'Editar opción' : 'Nueva opción'"
            :loading="form.processing"
            @submit="submit"
        >
            <FormInput
                id="item_name"
                v-model="form.name"
                label="Nombre"
                :error="form.errors.name"
                required
            />

            <FormInput
                id="item_code"
                v-model="form.code"
                label="Código"
                :icon="Hash"
                :error="form.errors.code"
                required
            />

            <FormInput
                id="item_sort"
                v-model="form.sort"
                type="number"
                label="Orden"
                min="0"
                :error="form.errors.sort"
            />

            <div class="md:col-span-2">
                <ActiveSwitch id="item_active" v-model="form.is_active" label="Opción activa" />
            </div>
        </FormDialog>
    </div>
</template>
