<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardCheck, Info, Pencil, TriangleAlert } from 'lucide-vue-next';

import AdminCard from '@/components/admin/AdminCard.vue';
import AttachmentsCard from '@/components/admin/AttachmentsCard.vue';
import DetailItem from '@/components/admin/DetailItem.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import SeverityBadge from '@/components/admin/SeverityBadge.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { formatDate, formatDateTime, formatTime, label } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Attachment, Option } from '@/types/admin';

defineProps<{
    incident: {
        id: number;
        title: string;
        type: string;
        severity: string;
        status: string;
        description: string;
        occurred_at: string;
        resolution: string | null;
        resolved_at: string | null;
        clinic: Option;
        space: { id: number; code: string; name: string } | null;
        equipment: { id: number; code: string; name: string; status: string } | null;
        clinical_session: { id: number; date: string; start_time: string; topic: string | null } | null;
        reporter: { name: string } | null;
        resolver: { name: string } | null;
        attachments: Attachment[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Incidencias', href: admin.clinicIncidents.index() },
            { title: 'Detalle', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="incident.title" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            :title="incident.title"
            :description="`${label(incident.type)} · ${incident.clinic.name}`"
            section="Incidencias"
            :icon="TriangleAlert"
            :action-icon="Pencil"
            action-label="Editar / dar seguimiento"
            :action-href="admin.clinicIncidents.edit(incident.id).url"
        >
            <template #details>
                <div class="mt-2 flex gap-2">
                    <StatusBadge :value="incident.status" />
                    <SeverityBadge :value="incident.severity" />
                </div>
            </template>
        </PageHeader>

        <div class="grid gap-5 xl:grid-cols-3">
            <div class="flex flex-col gap-5 xl:col-span-2">
                <AdminCard title="Detalle" :icon="Info">
                    <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <DetailItem label="Clínica">
                            <Link :href="admin.clinics.show(incident.clinic.id).url" class="text-sm font-semibold text-[#941B2B] hover:underline">
                                {{ incident.clinic.name }}
                            </Link>
                        </DetailItem>
                        <DetailItem label="Fecha y hora" :value="formatDateTime(incident.occurred_at)" />
                        <DetailItem label="Reportó" :value="incident.reporter?.name" />
                        <DetailItem label="Espacio">
                            <Link v-if="incident.space" :href="admin.spaces.show(incident.space.id).url" class="text-sm font-semibold text-[#941B2B] hover:underline">
                                {{ incident.space.code }} · {{ incident.space.name }}
                            </Link>
                            <span v-else class="text-sm text-[#858B93]">—</span>
                        </DetailItem>
                        <DetailItem label="Equipo">
                            <span v-if="incident.equipment" class="flex flex-wrap items-center gap-2 text-sm text-[#292D32]">
                                {{ incident.equipment.code }} · {{ incident.equipment.name }}
                                <StatusBadge :value="incident.equipment.status" />
                            </span>
                            <span v-else class="text-sm text-[#858B93]">—</span>
                        </DetailItem>
                        <DetailItem label="Sesión clínica">
                            <Link
                                v-if="incident.clinical_session"
                                :href="admin.clinicalSessions.show(incident.clinical_session.id).url"
                                class="text-sm font-semibold text-[#941B2B] hover:underline"
                            >
                                {{ formatDate(incident.clinical_session.date) }} {{ formatTime(incident.clinical_session.start_time) }}
                            </Link>
                            <span v-else class="text-sm text-[#858B93]">—</span>
                        </DetailItem>
                    </dl>

                    <p class="mt-5 whitespace-pre-line text-sm leading-6 text-[#525960]">{{ incident.description }}</p>
                </AdminCard>

                <AdminCard title="Resolución" :icon="ClipboardCheck">
                    <template v-if="incident.resolution">
                        <p class="whitespace-pre-line text-sm leading-6 text-[#525960]">{{ incident.resolution }}</p>
                        <p v-if="incident.resolved_at" class="mt-3 text-xs text-[#858B93]">
                            Cerrada el {{ formatDateTime(incident.resolved_at) }}<template v-if="incident.resolver"> por {{ incident.resolver.name }}</template>
                        </p>
                    </template>
                    <p v-else class="text-sm italic text-[#858B93]">
                        Pendiente. Usa "Editar / dar seguimiento" para cambiar el estado y registrar la resolución.
                    </p>
                </AdminCard>
            </div>

            <AttachmentsCard
                attachable-type="clinic_incident"
                :attachable-id="incident.id"
                :attachments="incident.attachments"
                title="Evidencias"
                description="Fotografías, reportes técnicos u órdenes de servicio."
            />
        </div>
    </div>
</template>
