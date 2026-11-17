<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Printer } from 'lucide-vue-next';

import AdminButton from '@/components/admin/AdminButton.vue';
import { formatDate, fullName, label } from '@/lib/admin';
import admin from '@/routes/admin';
import type { PersonName } from '@/types/admin';

defineProps<{
    activity: {
        id: number;
        type: string;
        title: string;
        starts_at: string;
        ends_at: string | null;
        hours: number | null;
        coordinator: { people: PersonName | null } | null;
    };
    participant: {
        id: number;
        grade: number | null;
        certificate_folio: string;
        certificate_issued_at: string;
        people: PersonName;
    };
}>();

const print = (): void => window.print();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Cursos y actividades', href: admin.activities.index() },
            { title: 'Constancia', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Constancia ${participant.certificate_folio}`" />

    <div class="flex h-full flex-1 flex-col gap-5 bg-[#F7F8FA] p-4 sm:p-6">
        <div class="flex gap-2 print:hidden">
            <Link :href="admin.activities.show(activity.id).url">
                <AdminButton variant="secondary" :icon="ArrowLeft">Volver</AdminButton>
            </Link>
            <AdminButton :icon="Printer" @click="print">Imprimir</AdminButton>
        </div>

        <article class="mx-auto aspect-[11/8.5] w-full max-w-4xl rounded-2xl border-8 border-double border-[#941B2B] bg-white p-10 text-center print:rounded-none">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#941B2B]">Universidad Juárez del Estado de Durango</p>
            <p class="mt-1 text-lg font-semibold text-[#292D32]">Facultad de Odontología</p>

            <h1 class="mt-10 text-4xl font-bold tracking-wide text-[#292D32]">CONSTANCIA</h1>
            <p class="mt-6 text-sm text-[#525960]">Se otorga a</p>
            <p class="mt-2 text-3xl font-semibold text-[#941B2B]">{{ fullName(participant.people) }}</p>

            <p class="mx-auto mt-6 max-w-2xl text-base leading-7 text-[#383D43]">
                por su participación en el {{ label(activity.type).toLowerCase() }}
                <span class="font-semibold">«{{ activity.title }}»</span>,
                realizado {{ activity.ends_at && activity.ends_at !== activity.starts_at ? `del ${formatDate(activity.starts_at)} al ${formatDate(activity.ends_at)}` : `el ${formatDate(activity.starts_at)}` }}<template v-if="activity.hours">, con una duración de {{ activity.hours }} horas</template><template v-if="participant.grade !== null">, con calificación de {{ participant.grade }}</template>.
            </p>

            <div class="mt-16 flex items-end justify-between px-10 text-xs text-[#525960]">
                <div class="text-left">
                    <p>Folio: <span class="font-semibold">{{ participant.certificate_folio }}</span></p>
                    <p>Expedida: {{ formatDate(participant.certificate_issued_at) }}</p>
                </div>
                <div class="w-64 border-t border-[#292D32] pt-1">
                    {{ activity.coordinator ? fullName(activity.coordinator.people) : 'Coordinación' }}
                    <p class="text-[11px] text-[#858B93]">Coordinador(a)</p>
                </div>
            </div>
        </article>
    </div>
</template>
