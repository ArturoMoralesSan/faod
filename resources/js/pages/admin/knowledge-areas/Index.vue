<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { BookOpen, Pencil, Plus, Trash2 } from 'lucide-vue-next';
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

interface KnowledgeArea {
    id: number;
    name: string;
    is_active: boolean;
    teachers_count: number;
}

defineProps<{
    areas: KnowledgeArea[];
}>();

const dialogOpen = ref(false);
const editing = ref<KnowledgeArea | null>(null);

const form = useForm({
    name: '',
    is_active: true,
});

const openCreate = (): void => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
};

const openEdit = (area: KnowledgeArea): void => {
    editing.value = area;
    form.clearErrors();
    form.name = area.name;
    form.is_active = area.is_active;
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
        form.put(admin.knowledgeAreas.update(editing.value.id).url, options);
    } else {
        form.post(admin.knowledgeAreas.store().url, options);
    }
};

const destroy = (area: KnowledgeArea): void => {
    confirmDelete(
        '¿Eliminar área?',
        `Se eliminará "${area.name}" y se quitará de los docentes que la tengan.`,
        admin.knowledgeAreas.destroy(area.id).url,
        { preserveState: true },
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Áreas de conocimiento', href: admin.knowledgeAreas.index() },
        ],
    },
});
</script>

<template>
    <Head title="Áreas de conocimiento" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Áreas de conocimiento"
            description="Catálogo de áreas que se asignan a docentes y especialistas."
            section="Docentes"
            :icon="BookOpen"
            :action-icon="Plus"
            action-label="Nueva área"
            @action="openCreate"
        />

        <AdminCard :padding="false">
            <table v-if="areas.length" class="w-full border-collapse text-left text-sm">
                <thead class="bg-[#F7F8FA] text-xs font-semibold uppercase tracking-wide text-[#383D43]">
                    <tr>
                        <th class="px-5 py-4 sm:px-6">Área</th>
                        <th class="px-5 py-4 sm:px-6">Docentes</th>
                        <th class="px-5 py-4 sm:px-6">Estado</th>
                        <th class="px-5 py-4 text-right sm:px-6">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-[#E3E5E8]">
                    <tr v-for="area in areas" :key="area.id" class="hover:bg-[#FAF3F4]">
                        <td class="px-5 py-4 font-semibold text-[#292D32] sm:px-6">{{ area.name }}</td>
                        <td class="px-5 py-4 text-[#525960] sm:px-6">{{ area.teachers_count }}</td>
                        <td class="px-5 py-4 sm:px-6"><StatusBadge :value="area.is_active" /></td>
                        <td class="px-5 py-4 sm:px-6">
                            <div class="flex justify-end gap-1.5">
                                <AdminButton variant="secondary" :icon="Pencil" @click="openEdit(area)">
                                    Editar
                                </AdminButton>

                                <button
                                    type="button"
                                    class="inline-flex h-12 w-12 items-center justify-center rounded-full border border-[#E8C7CC] text-[#941B2B] transition-colors hover:bg-[#721421] hover:text-white"
                                    title="Eliminar"
                                    @click="destroy(area)"
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
                :icon="BookOpen"
                title="Sin áreas registradas"
                description="Agrega la primera área de conocimiento."
            />
        </AdminCard>

        <FormDialog
            v-model:open="dialogOpen"
            :title="editing ? 'Editar área' : 'Nueva área'"
            :loading="form.processing"
            @submit="submit"
        >
            <div class="md:col-span-2">
                <FormInput
                    id="area_name"
                    v-model="form.name"
                    label="Nombre"
                    placeholder="Ej. Biomateriales"
                    :error="form.errors.name"
                    required
                />
            </div>

            <div class="md:col-span-2">
                <ActiveSwitch id="area_active" v-model="form.is_active" label="Área activa" />
            </div>
        </FormDialog>
    </div>
</template>
