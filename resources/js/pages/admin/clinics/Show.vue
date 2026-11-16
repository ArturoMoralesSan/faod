<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { DoorOpen, Hospital, Info, Pencil, Plus } from 'lucide-vue-next';

import AdminCard from '@/components/admin/AdminCard.vue';
import DetailItem from '@/components/admin/DetailItem.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { label } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option } from '@/types/admin';

defineProps<{
    clinic: {
        id: number;
        code: string;
        name: string;
        location: string | null;
        description: string | null;
        is_active: boolean;
        department: Option | null;
        spaces: {
            id: number;
            code: string;
            name: string;
            type: string;
            capacity: number | null;
            is_active: boolean;
        }[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Clínicas', href: admin.clinics.index() },
            { title: 'Detalle', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="clinic.name" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            :title="clinic.name"
            :description="clinic.location ?? clinic.code"
            section="Clínicas"
            :icon="Hospital"
            :action-icon="Pencil"
            action-label="Editar clínica"
            :action-href="admin.clinics.edit(clinic.id).url"
        />

        <AdminCard title="Información general" :icon="Info">
            <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <DetailItem label="Clave" :value="clinic.code" />
                <DetailItem label="Departamento" :value="clinic.department?.name" />
                <DetailItem label="Ubicación" :value="clinic.location" />
                <DetailItem label="Estado"><StatusBadge :value="clinic.is_active" /></DetailItem>
            </dl>

            <p v-if="clinic.description" class="mt-5 whitespace-pre-line text-sm leading-6 text-[#525960]">
                {{ clinic.description }}
            </p>
        </AdminCard>

        <AdminCard title="Consultorios y espacios" :icon="DoorOpen" :padding="false">
            <div class="flex justify-end border-b border-[#E3E5E8] px-5 py-3 sm:px-6">
                <Link
                    :href="admin.spaces.create().url"
                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#941B2B] hover:underline"
                >
                    <Plus class="h-4 w-4" /> Nuevo espacio
                </Link>
            </div>

            <ul v-if="clinic.spaces.length" class="divide-y divide-[#E3E5E8]">
                <li v-for="space in clinic.spaces" :key="space.id">
                    <Link
                        :href="admin.spaces.show(space.id).url"
                        class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-[#FAF3F4] sm:px-6"
                    >
                        <div>
                            <p class="text-sm font-semibold text-[#292D32]">{{ space.name }}</p>
                            <p class="text-xs text-[#858B93]">
                                {{ space.code }} · {{ label(space.type) }}
                                <template v-if="space.capacity"> · {{ space.capacity }} lugares</template>
                            </p>
                        </div>
                        <StatusBadge :value="space.is_active" />
                    </Link>
                </li>
            </ul>

            <EmptyState v-else :icon="DoorOpen" title="Sin espacios" description="Registra los consultorios de esta clínica." />
        </AdminCard>
    </div>
</template>
