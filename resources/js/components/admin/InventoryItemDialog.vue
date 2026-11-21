<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

import ActiveSwitch from '@/components/admin/ActiveSwitch.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import { enumOptions, recordOptions } from '@/lib/admin';
import admin from '@/routes/admin';
import type { InventoryItemData, Option } from '@/types/admin';

/**
 * Alta y edición de un artículo de inventario.
 */
const props = defineProps<{
    item: InventoryItemData | null;
    suppliers: Option[];
    categories: string[];
    kinds: string[];
}>();

const open = defineModel<boolean>('open', { required: true });

const form = useForm({
    code: '',
    name: '',
    category: null as string | null,
    kind: 'material',
    unit: 'pza',
    minimum_stock: 0 as number | null,
    tracks_expiration: false,
    default_supplier_id: null as number | null,
    description: '',
    is_active: true,
});

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    form.reset();
    form.clearErrors();

    if (props.item) {
        Object.assign(form, {
            ...props.item,
            category: props.item.category ?? null,
            description: props.item.description ?? '',
        });
    }
});

const submit = (): void => {
    const options = { preserveScroll: true, onSuccess: () => (open.value = false) };

    form.transform((data) => ({ ...data, category: data.category || null, description: data.description || null }));

    if (props.item) {
        form.put(admin.inventory.update(props.item.id).url, options);
    } else {
        form.post(admin.inventory.store().url, options);
    }
};
</script>

<template>
    <FormDialog v-model:open="open" :title="item ? 'Editar artículo' : 'Nuevo artículo'" :loading="form.processing" @submit="submit">
        <FormInput id="it_code" v-model="form.code" label="Clave" placeholder="Ej. ANE-01" :error="form.errors.code" required />
        <FormSelect
            id="it_category"
            v-model="form.category"
            label="Categoría"
            placeholder="Sin categoría"
            help="Se administran en Configuración → Catálogos → Categorías de inventario."
            :options="categories.map((c) => ({ value: c, label: c }))"
            :error="form.errors.category"
            nullable
        />
        <FormSelect id="it_kind" v-model="form.kind" label="Tipo" :options="enumOptions(kinds)" :error="form.errors.kind" required />
        <div class="md:col-span-2">
            <FormInput id="it_name" v-model="form.name" label="Nombre" :error="form.errors.name" required />
        </div>
        <FormInput id="it_unit" v-model="form.unit" label="Unidad" placeholder="pza, caja, ml, g" :error="form.errors.unit" required />
        <FormInput id="it_min" v-model="form.minimum_stock" type="number" step="0.01" min="0" label="Existencia mínima" help="Avisa cuando la existencia total baje de aquí." :error="form.errors.minimum_stock" required />
        <div class="md:col-span-2">
            <FormSelect id="it_supplier" v-model="form.default_supplier_id" label="Proveedor habitual" placeholder="Sin proveedor" :options="recordOptions(suppliers)" nullable />
        </div>
        <div class="md:col-span-2">
            <FormTextarea id="it_desc" v-model="form.description" label="Descripción" :rows="2" :error="form.errors.description" />
        </div>
        <div class="md:col-span-2 flex flex-col gap-3">
            <ActiveSwitch v-model="form.tracks_expiration" label="Controla lote y caducidad" />
            <ActiveSwitch v-model="form.is_active" label="Artículo activo" />
        </div>
    </FormDialog>
</template>
