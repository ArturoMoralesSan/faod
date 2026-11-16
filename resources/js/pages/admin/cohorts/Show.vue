<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarClock, GraduationCap, Info, Pencil, UsersRound } from 'lucide-vue-next';

import AdminCard from '@/components/admin/AdminCard.vue';
import DetailItem from '@/components/admin/DetailItem.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { formatDate, fullName } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option, StudentOption } from '@/types/admin';

defineProps<{
    cohort: {
        id: number;
        name: string;
        status: string;
        starts_at: string | null;
        ends_at: string | null;
        academic_program: Option;
        groups: {
            id: number;
            code: string;
            period_number: number;
            academic_period: Option;
        }[];
        enrollments: {
            id: number;
            status: string;
            student: StudentOption;
        }[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Generaciones', href: admin.cohorts.index() },
            { title: 'Detalle', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Generación ${cohort.name}`" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            :title="`Generación ${cohort.name}`"
            :description="cohort.academic_program.name"
            section="Generaciones"
            :icon="UsersRound"
            :action-icon="Pencil"
            action-label="Editar generación"
            :action-href="admin.cohorts.edit(cohort.id).url"
        />

        <AdminCard title="Información general" :icon="Info">
            <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <DetailItem label="Programa">
                    <Link :href="admin.academicPrograms.show(cohort.academic_program.id).url" class="text-[#941B2B] hover:underline">
                        {{ cohort.academic_program.name }}
                    </Link>
                </DetailItem>
                <DetailItem label="Inicio" :value="formatDate(cohort.starts_at)" />
                <DetailItem label="Término" :value="formatDate(cohort.ends_at)" />
                <DetailItem label="Estado"><StatusBadge :value="cohort.status" /></DetailItem>
            </dl>
        </AdminCard>

        <div class="grid gap-5 lg:grid-cols-2">
            <AdminCard :title="`Alumnos (${cohort.enrollments.length})`" :icon="GraduationCap" :padding="false">
                <ul v-if="cohort.enrollments.length" class="divide-y divide-[#E3E5E8]">
                    <li v-for="enrollment in cohort.enrollments" :key="enrollment.id">
                        <Link
                            :href="admin.students.show(enrollment.id).url"
                            class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-[#FAF3F4] sm:px-6"
                        >
                            <div>
                                <p class="text-sm font-semibold text-[#292D32]">{{ fullName(enrollment.student.people) }}</p>
                                <p class="text-xs text-[#858B93]">{{ enrollment.student.registration_number }}</p>
                            </div>
                            <StatusBadge :value="enrollment.status" />
                        </Link>
                    </li>
                </ul>
                <p v-else class="px-5 py-4 text-sm italic text-[#858B93] sm:px-6">Sin alumnos inscritos.</p>
            </AdminCard>

            <AdminCard title="Grupos" :icon="CalendarClock" :padding="false">
                <ul v-if="cohort.groups.length" class="divide-y divide-[#E3E5E8]">
                    <li v-for="group in cohort.groups" :key="group.id">
                        <Link
                            :href="admin.groups.show(group.id).url"
                            class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-[#FAF3F4] sm:px-6"
                        >
                            <div>
                                <p class="text-sm font-semibold text-[#292D32]">Grupo {{ group.code }}</p>
                                <p class="text-xs text-[#858B93]">
                                    {{ group.academic_period.name }} · Semestre {{ group.period_number }}
                                </p>
                            </div>
                        </Link>
                    </li>
                </ul>
                <p v-else class="px-5 py-4 text-sm italic text-[#858B93] sm:px-6">Sin grupos.</p>
            </AdminCard>
        </div>
    </div>
</template>
