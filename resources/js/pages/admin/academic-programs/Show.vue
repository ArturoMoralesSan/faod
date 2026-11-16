<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, BookOpen, GraduationCap, Info, Pencil, Plus, Target, Users, UsersRound } from 'lucide-vue-next';
import { computed } from 'vue';

import AdminCard from '@/components/admin/AdminCard.vue';
import AttachmentsCard from '@/components/admin/AttachmentsCard.vue';
import DetailItem from '@/components/admin/DetailItem.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { formatDate, fullName, label } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Attachment, Option, PersonName } from '@/types/admin';

interface Program {
    id: number;
    code: string;
    name: string;
    type: string;
    description: string | null;
    duration_value: number | null;
    duration_unit: string | null;
    total_periods: number | null;
    is_active: boolean;
    specialty: Option | null;
    department: Option | null;
    objectives: { id: number; type: string; description: string }[];
    teachers: {
        id: number;
        people: PersonName | null;
        pivot: { role: string; starts_at: string | null; ends_at: string | null };
    }[];
    study_plans: { id: number; name: string; version: string; is_active: boolean; subjects_count: number }[];
    cohorts: { id: number; name: string; status: string; starts_at: string | null; active_students_count: number }[];
    attachments: Attachment[];
}

const props = defineProps<{
    program: Program;
}>();

const objectivesByType = computed(() => {
    const groups: Record<string, string[]> = {};

    props.program.objectives.forEach((objective) => {
        (groups[objective.type] ??= []).push(objective.description);
    });

    return groups;
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Programas académicos', href: admin.academicPrograms.index() },
            { title: 'Detalle', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="program.name" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            :title="program.name"
            :description="`${label(program.type)} · ${program.code}`"
            section="Programas académicos"
            :icon="GraduationCap"
            :action-icon="Pencil"
            action-label="Editar programa"
            :action-href="admin.academicPrograms.edit(program.id).url"
        >
            <template #actions>
                <Link
                    :href="admin.academicPrograms.index().url"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full border border-[#E3E5E8] bg-white px-[23px] text-[13px] font-semibold text-[#383D43] transition-colors hover:border-[#941B2B] hover:bg-[#F9ECEE] hover:text-[#941B2B]"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Volver
                </Link>
            </template>
        </PageHeader>

        <div class="grid gap-5 xl:grid-cols-3">
            <div class="flex flex-col gap-5 xl:col-span-2">
                <AdminCard title="Información general" :icon="Info">
                    <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <DetailItem label="Especialidad" :value="program.specialty?.name" />
                        <DetailItem label="Departamento" :value="program.department?.name" />
                        <DetailItem
                            label="Duración"
                            :value="program.duration_value ? `${program.duration_value} ${label(program.duration_unit).toLowerCase()}` : null"
                        />
                        <DetailItem label="Semestres / periodos" :value="program.total_periods" />
                        <DetailItem label="Estado">
                            <StatusBadge :value="program.is_active" />
                        </DetailItem>
                    </dl>

                    <p v-if="program.description" class="mt-5 whitespace-pre-line text-sm leading-6 text-[#525960]">
                        {{ program.description }}
                    </p>
                </AdminCard>

                <AdminCard title="Objetivos y competencias" :icon="Target">
                    <div v-if="program.objectives.length" class="flex flex-col gap-5">
                        <div v-for="(items, type) in objectivesByType" :key="type">
                            <h3 class="mb-2 text-sm font-semibold text-[#292D32]">{{ label(String(type)) }}</h3>
                            <ul class="list-disc space-y-1 pl-5 text-sm text-[#525960]">
                                <li v-for="(item, index) in items" :key="index" class="whitespace-pre-line">{{ item }}</li>
                            </ul>
                        </div>
                    </div>
                    <p v-else class="text-sm italic text-[#858B93]">Sin objetivos registrados.</p>
                </AdminCard>

                <AdminCard title="Generaciones" :icon="UsersRound" :padding="false">
                    <div class="flex justify-end border-b border-[#E3E5E8] px-5 py-3 sm:px-6">
                        <Link
                            :href="admin.cohorts.create().url"
                            class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#941B2B] hover:underline"
                        >
                            <Plus class="h-4 w-4" /> Nueva generación
                        </Link>
                    </div>

                    <ul v-if="program.cohorts.length" class="divide-y divide-[#E3E5E8]">
                        <li v-for="cohort in program.cohorts" :key="cohort.id">
                            <Link
                                :href="admin.cohorts.show(cohort.id).url"
                                class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-[#FAF3F4] sm:px-6"
                            >
                                <div>
                                    <p class="font-semibold text-[#292D32]">{{ cohort.name }}</p>
                                    <p class="text-xs text-[#858B93]">
                                        Inicio: {{ formatDate(cohort.starts_at) }} · {{ cohort.active_students_count }} alumnos activos
                                    </p>
                                </div>
                                <StatusBadge :value="cohort.status" />
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="px-5 py-4 text-sm italic text-[#858B93] sm:px-6">Sin generaciones.</p>
                </AdminCard>
            </div>

            <div class="flex flex-col gap-5">
                <AdminCard title="Docentes" :icon="Users">
                    <ul v-if="program.teachers.length" class="flex flex-col gap-3">
                        <li v-for="teacher in program.teachers" :key="`${teacher.id}-${teacher.pivot.role}`">
                            <Link :href="admin.teachers.show(teacher.id).url" class="group block">
                                <p class="text-sm font-semibold text-[#292D32] group-hover:text-[#941B2B]">
                                    {{ fullName(teacher.people) }}
                                </p>
                                <p class="text-xs text-[#858B93]">{{ label(teacher.pivot.role) }}</p>
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="text-sm italic text-[#858B93]">Sin docentes asignados.</p>
                </AdminCard>

                <AdminCard title="Planes académicos" :icon="BookOpen">
                    <ul v-if="program.study_plans.length" class="flex flex-col gap-3">
                        <li v-for="plan in program.study_plans" :key="plan.id">
                            <Link :href="admin.studyPlans.show(plan.id).url" class="group flex items-center justify-between gap-2">
                                <span>
                                    <span class="block text-sm font-semibold text-[#292D32] group-hover:text-[#941B2B]">
                                        {{ plan.name }}
                                    </span>
                                    <span class="text-xs text-[#858B93]">
                                        Versión {{ plan.version }} · {{ plan.subjects_count }} asignaturas
                                    </span>
                                </span>
                                <StatusBadge :value="plan.is_active" />
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="text-sm italic text-[#858B93]">Sin planes registrados.</p>
                </AdminCard>

                <AttachmentsCard
                    attachable-type="academic_program"
                    :attachable-id="program.id"
                    :attachments="program.attachments"
                />
            </div>
        </div>
    </div>
</template>
