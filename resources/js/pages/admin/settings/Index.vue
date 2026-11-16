<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Save, Settings } from 'lucide-vue-next';

import AdminButton from '@/components/admin/AdminButton.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { label } from '@/lib/admin';
import admin from '@/routes/admin';

interface Setting {
    id: number;
    group: string;
    key: string;
    label: string | null;
    value: unknown;
}

const props = defineProps<{
    settings: Record<string, Setting[]>;
}>();

const allSettings = Object.values(props.settings).flat();

const form = useForm({
    settings: allSettings.map((setting) => ({
        key: setting.key,
        value: setting.value as string | number | boolean | null,
    })),
});

const indexOf = (key: string): number =>
    form.settings.findIndex((setting) => setting.key === key);

const submit = (): void => {
    form.put(admin.settings.update().url, { preserveScroll: true });
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Configuración', href: admin.settings.index() },
        ],
    },
});
</script>

<template>
    <Head title="Configuración" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Configuración general"
            description="Parámetros generales del sistema."
            section="Administración"
            :icon="Settings"
        />

        <form v-if="allSettings.length" class="flex flex-col gap-5" @submit.prevent="submit">
            <AdminCard
                v-for="(items, group) in settings"
                :key="group"
                :title="label(String(group))"
                :icon="Settings"
            >
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div v-for="setting in items" :key="setting.key" class="form-field">
                        <template v-if="typeof setting.value === 'boolean'">
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-[#E3E5E8] bg-white p-4">
                                <input
                                    v-model="form.settings[indexOf(setting.key)].value"
                                    type="checkbox"
                                    class="form-checkbox shrink-0"
                                />
                                <span class="text-sm font-semibold text-[#292D32]">
                                    {{ setting.label ?? setting.key }}
                                </span>
                            </label>
                        </template>

                        <template v-else>
                            <label :for="setting.key" class="form-label">
                                {{ setting.label ?? setting.key }}
                            </label>

                            <input
                                :id="setting.key"
                                v-model="form.settings[indexOf(setting.key)].value"
                                :type="typeof setting.value === 'number' ? 'number' : 'text'"
                                class="form-input"
                            />
                        </template>

                        <p class="form-help font-mono">{{ setting.key }}</p>
                    </div>
                </div>
            </AdminCard>

            <div class="flex justify-end border-t border-[#DCE7DF] pt-5">
                <AdminButton
                    type="submit"
                    :icon="Save"
                    :loading="form.processing"
                    loading-text="Guardando..."
                >
                    Guardar configuración
                </AdminButton>
            </div>
        </form>

        <AdminCard v-else>
            <EmptyState
                :icon="Settings"
                title="Sin parámetros"
                description="Los parámetros se registran desde el seeder de configuración."
            />
        </AdminCard>
    </div>
</template>
