<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ListTree, Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import ActiveSwitch from '@/components/admin/ActiveSwitch.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { confirmDelete, recordOptions } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option } from '@/types/admin';

interface Diagnosis {
    id: number;
    code: string;
    name: string;
    category: string | null;
    specialty_id: number | null;
    specialty: Option | null;
    description: string | null;
    is_active: boolean;
    patient_diagnoses_count: number;
}

const props = defineProps<{
    diagnoses: Diagnosis[];
    categories: string[];
    filters: { search?: string; specialty_id?: string };
    specialties: Option[];
}>();

const search = ref(props.filters.search ?? '');
const specialtyFilter = ref(props.filters.specialty_id ?? '');

const submitSearch = (): void => {
    router.get(
        admin.diagnoses.index().url,
        { search: search.value.trim() || undefined, specialty_id: specialtyFilter.value || undefined },
        { preserveState: true, replace: true },
    );
};

const grouped = computed(() => {
    const groups = new Map<string, Diagnosis[]>();

    props.diagnoses.forEach((d) => {
        const key = d.category ?? 'Sin categoría';
        groups.set(key, [...(groups.get(key) ?? []), d]);
    });

    return [...groups.entries()];
});

const dialogOpen = ref(false);
const editing = ref<Diagnosis | null>(null);

const form = useForm({ code: '', name: '', category: '', specialty_id: null as number | null, description: '', is_active: true });

const open = (diagnosis: Diagnosis | null = null): void => {
    editing.value = diagnosis;
    form.reset();
    form.clearErrors();

    if (diagnosis) {
        form.code = diagnosis.code;
        form.name = diagnosis.name;
        form.category = diagnosis.category ?? '';
        form.specialty_id = diagnosis.specialty_id;
        form.description = diagnosis.description ?? '';
        form.is_active = diagnosis.is_active;
    }

    dialogOpen.value = true;
};

const submit = (): void => {
    const options = { preserveScroll: true, onSuccess: () => (dialogOpen.value = false) };

    form.transform((data) => ({ ...data, category: data.category || null, description: data.description || null }));

    if (editing.value) {
        form.put(admin.diagnoses.update(editing.value.id).url, options);
    } else {
        form.post(admin.diagnoses.store().url, options);
    }
};

const destroy = (diagnosis: Diagnosis): void => {
    confirmDelete('¿Eliminar diagnóstico?', `Se eliminará "${diagnosis.code} ${diagnosis.name}" del catálogo.`, admin.diagnoses.destroy(diagnosis.id).url, { preserveState: true });
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Catálogo de diagnósticos', href: admin.diagnoses.index() },
        ],
    },
});
</script>

<template>
    <Head title="Catálogo de diagnósticos" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Catálogo de diagnósticos"
            description="Diagnósticos que se eligen en el expediente (incluye códigos CIE-10 de padecimientos bucales)."
            section="Atención clínica"
            :icon="ListTree"
            :action-icon="Plus"
            action-label="Nuevo diagnóstico"
            @action="open()"
        />

        <form class="flex max-w-2xl flex-wrap gap-2" @submit.prevent="submitSearch">
            <div class="relative min-w-[220px] flex-1">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#858B93]" />
                <input v-model="search" type="search" placeholder="Buscar por código o nombre..." class="form-input pl-10" />
            </div>
            <FilterSelect v-model="specialtyFilter" placeholder="Todas las especialidades" :options="recordOptions(specialties)" @change="submitSearch" />
        </form>

        <AdminCard v-for="[category, items] in grouped" :key="category" :title="category" :padding="false">
            <ul class="divide-y divide-[#E3E5E8]">
                <li v-for="d in items" :key="d.id" class="flex items-center justify-between gap-3 px-5 py-3 sm:px-6">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-[#292D32]">
                            <span class="text-[#941B2B]">{{ d.code }}</span> · {{ d.name }}
                            <span v-if="d.specialty" class="ml-1 rounded-full bg-[#F9ECEE] px-2 py-0.5 text-[11px] font-medium text-[#941B2B]">{{ d.specialty.name }}</span>
                        </p>
                        <p class="text-xs text-[#858B93]">Usado en {{ d.patient_diagnoses_count }} expediente(s)</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        <StatusBadge :value="d.is_active" />
                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#383D43] hover:bg-[#F1F2F4]" title="Editar" @click="open(d)">
                            <Pencil class="h-4 w-4" />
                        </button>
                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F1F2F4]" title="Eliminar" @click="destroy(d)">
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </li>
            </ul>
        </AdminCard>

        <AdminCard v-if="!diagnoses.length">
            <EmptyState :icon="ListTree" title="Sin diagnósticos" description="No hay diagnósticos que coincidan." />
        </AdminCard>

        <FormDialog v-model:open="dialogOpen" :title="editing ? 'Editar diagnóstico' : 'Nuevo diagnóstico'" :loading="form.processing" @submit="submit">
            <FormInput id="dx_code" v-model="form.code" label="Código" placeholder="Ej. K02.1" :error="form.errors.code" required />
            <FormInput
                id="dx_category"
                v-model="form.category"
                label="Categoría"
                :placeholder="categories.length ? `Ej. ${categories.slice(0, 3).join(', ')}` : 'Ej. Caries'"
                :error="form.errors.category"
            />
            <div class="md:col-span-2">
                <FormSelect id="dx_specialty" v-model="form.specialty_id" label="Especialidad" placeholder="General (todas)" :options="recordOptions(specialties)" :error="form.errors.specialty_id" nullable />
            </div>
            <div class="md:col-span-2">
                <FormInput id="dx_name" v-model="form.name" label="Nombre" :error="form.errors.name" required />
            </div>
            <div class="md:col-span-2">
                <FormTextarea id="dx_description" v-model="form.description" label="Descripción" :rows="2" :error="form.errors.description" />
            </div>
            <div class="md:col-span-2">
                <ActiveSwitch v-model="form.is_active" label="Disponible en el expediente" />
            </div>
        </FormDialog>
    </div>
</template>
