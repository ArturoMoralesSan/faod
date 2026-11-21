<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    CalendarCheck,
    DoorOpen,
    Hospital,
    Info,
    LayoutGrid,
    Pencil,
    Plus,
    TriangleAlert,
    UserRoundCheck,
    Wrench,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

import ActiveSwitch from '@/components/admin/ActiveSwitch.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import DetailItem from '@/components/admin/DetailItem.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import SeverityBadge from '@/components/admin/SeverityBadge.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import RecordList from '@/components/admin/TeacherRecordList.vue';
import { confirmDelete, enumOptions, formatDate, formatTime, fullName, label, recordOptions, toDateInput } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Option, PersonName, SelectOption, TeacherOption } from '@/types/admin';

type RecordType = 'areas' | 'supervisors' | 'equipment';

interface Area { id: number; name: string; description: string | null; is_active: boolean; specialty_id: number | null; specialty: Option | null }
interface Supervisor {
    id: number;
    teacher_profile_id: number;
    clinic_area_id: number | null;
    role: string;
    starts_at: string | null;
    ends_at: string | null;
    teacher: { people: PersonName | null } | null;
    area: Option | null;
}
interface Equipment {
    id: number;
    kind: string;
    space_id: number | null;
    code: string;
    name: string;
    brand: string | null;
    model: string | null;
    serial_number: string | null;
    status: string;
    acquired_at: string | null;
    last_maintenance_at: string | null;
    next_maintenance_at: string | null;
    notes: string | null;
    space: { id: number; code: string; name: string } | null;
}

const props = defineProps<{
    clinic: {
        id: number;
        code: string;
        name: string;
        location: string | null;
        description: string | null;
        is_active: boolean;
        department: Option | null;
        areas: Area[];
        supervisors: Supervisor[];
        equipment: Equipment[];
        spaces: {
            id: number;
            code: string;
            name: string;
            type: string;
            capacity: number | null;
            is_active: boolean;
            area: Option | null;
        }[];
    };
    upcomingSessions: {
        id: number;
        date: string;
        start_time: string;
        end_time: string;
        status: string;
        topic: string | null;
        space: { code: string; name: string } | null;
        group: { code: string } | null;
        supervisor: { people: PersonName | null } | null;
    }[];
    openIncidents: { id: number; title: string; type: string; severity: string; status: string; occurred_at: string }[];
    teachers: TeacherOption[];
    supervisorRoles: string[];
    equipmentStatuses: string[];
    equipmentKinds: string[];
    specialties: Option[];
}>();

const teacherOptions = computed<SelectOption[]>(() =>
    props.teachers.map((t) => ({ value: t.id, label: fullName(t.people) })),
);
const areaOptions = computed(() => recordOptions(props.clinic.areas));
const spaceOptions = computed(() => recordOptions(props.clinic.spaces, true));

const today = new Date().toISOString().substring(0, 10);
const maintenanceDue = (e: Equipment) => !!e.next_maintenance_at && e.next_maintenance_at.substring(0, 10) <= today;

/* ------------------------- Registros de la clínica ------------------------- */

const dialogOpen = ref(false);
const recordType = ref<RecordType>('areas');
const editingId = ref<number | null>(null);

const emptyRecord = {
    // Área
    name: '', description: '', is_active: true, specialty_id: null as number | null,
    // Supervisor
    teacher_profile_id: null as number | null, clinic_area_id: null as number | null, role: 'supervisor', starts_at: '', ends_at: '',
    // Equipo
    kind: 'equipo', space_id: null as number | null, code: '', brand: '', model: '', serial_number: '', status: 'operativo',
    acquired_at: '', last_maintenance_at: '', next_maintenance_at: '', notes: '',
};

const recordForm = useForm({ ...emptyRecord });

const FIELDS: Record<RecordType, (keyof typeof emptyRecord)[]> = {
    areas: ['specialty_id', 'name', 'description', 'is_active'],
    supervisors: ['teacher_profile_id', 'clinic_area_id', 'role', 'starts_at', 'ends_at'],
    equipment: ['kind', 'space_id', 'code', 'name', 'brand', 'model', 'serial_number', 'status', 'acquired_at', 'last_maintenance_at', 'next_maintenance_at', 'notes'],
};

const TITLES: Record<RecordType, string> = {
    areas: 'área',
    supervisors: 'supervisor',
    equipment: 'equipo',
};

const sources: Record<RecordType, () => { id: number }[]> = {
    areas: () => props.clinic.areas,
    supervisors: () => props.clinic.supervisors,
    equipment: () => props.clinic.equipment,
};

const openRecord = (type: RecordType, id: number | null = null): void => {
    recordType.value = type;
    editingId.value = id;
    recordForm.defaults({ ...emptyRecord });
    recordForm.reset();
    recordForm.clearErrors();

    const record = id ? sources[type]().find((item) => item.id === id) : null;

    if (record) {
        FIELDS[type].forEach((field) => {
            const value = (record as Record<string, unknown>)[field];

            (recordForm as unknown as Record<string, unknown>)[field] = field.endsWith('_at')
                ? toDateInput(value as string | null)
                : (value ?? (typeof emptyRecord[field] === 'string' ? '' : null));
        });
    }

    dialogOpen.value = true;
};

const submitRecord = (): void => {
    const type = recordType.value;
    const options = { preserveScroll: true, onSuccess: () => (dialogOpen.value = false) };

    recordForm.transform((data) =>
        Object.fromEntries(FIELDS[type].map((field) => [field, data[field] === '' ? null : data[field]])),
    );

    if (editingId.value) {
        recordForm.put(
            admin.clinics.records.update({ clinic: props.clinic.id, type, record: editingId.value }).url,
            options,
        );
    } else {
        recordForm.post(admin.clinics.records.store({ clinic: props.clinic.id, type }).url, options);
    }
};

const removeRecord = (type: RecordType, id: number): void => {
    confirmDelete(
        `¿Eliminar ${TITLES[type]}?`,
        'Esta acción no se puede deshacer.',
        admin.clinics.records.destroy({ clinic: props.clinic.id, type, record: id }).url,
        { preserveState: true },
    );
};

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

        <div class="grid gap-5 xl:grid-cols-3">
            <div class="flex flex-col gap-5 xl:col-span-2">
                <!-- Próximas sesiones -->
                <AdminCard title="Próximas sesiones clínicas" :icon="CalendarCheck" :padding="false">
                    <div class="flex justify-end gap-4 border-b border-[#E3E5E8] px-5 py-3 sm:px-6">
                        <Link
                            :href="admin.clinicalSessions.index({ query: { clinic_id: clinic.id } }).url"
                            class="text-sm font-semibold text-[#525960] hover:underline"
                        >
                            Ver todas
                        </Link>
                        <Link
                            :href="admin.clinicalSessions.create({ query: { clinic_id: clinic.id } }).url"
                            class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#941B2B] hover:underline"
                        >
                            <Plus class="h-4 w-4" /> Programar sesión
                        </Link>
                    </div>

                    <ul v-if="upcomingSessions.length" class="divide-y divide-[#E3E5E8]">
                        <li v-for="session in upcomingSessions" :key="session.id">
                            <Link
                                :href="admin.clinicalSessions.show(session.id).url"
                                class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-[#FAF3F4] sm:px-6"
                            >
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-[#292D32]">
                                        {{ formatDate(session.date) }} · {{ formatTime(session.start_time) }}–{{ formatTime(session.end_time) }}
                                    </p>
                                    <p class="truncate text-xs text-[#858B93]">
                                        {{ [session.topic, session.space?.name, session.group ? `Grupo ${session.group.code}` : null, session.supervisor ? fullName(session.supervisor.people) : null].filter(Boolean).join(' · ') }}
                                    </p>
                                </div>
                                <StatusBadge :value="session.status" />
                            </Link>
                        </li>
                    </ul>

                    <EmptyState v-else :icon="CalendarCheck" title="Sin sesiones próximas" description="Programa una práctica clínica en esta clínica." />
                </AdminCard>

                <!-- Consultorios -->
                <AdminCard title="Consultorios y espacios" :icon="DoorOpen" :padding="false">
                    <div class="flex justify-end border-b border-[#E3E5E8] px-5 py-3 sm:px-6">
                        <Link
                            :href="admin.spaces.create({ query: { clinic_id: clinic.id } }).url"
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
                                        <template v-if="space.area"> · {{ space.area.name }}</template>
                                        <template v-if="space.capacity"> · {{ space.capacity }} lugares</template>
                                    </p>
                                </div>
                                <StatusBadge :value="space.is_active" />
                            </Link>
                        </li>
                    </ul>

                    <EmptyState v-else :icon="DoorOpen" title="Sin espacios" description="Registra los consultorios de esta clínica." />
                </AdminCard>

                <!-- Equipos -->
                <AdminCard title="Equipos e instrumental" description="Unidades dentales, autoclaves, rayos X e instrumental de la clínica." :icon="Wrench">
                    <ul v-if="clinic.equipment.length" class="flex flex-col gap-3">
                        <li
                            v-for="item in clinic.equipment"
                            :key="item.id"
                            class="flex items-start justify-between gap-3 rounded-xl border border-[#E3E5E8] p-3"
                        >
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-[#292D32]">
                                    {{ item.name }}
                                    <span class="ml-1 rounded-full bg-[#F7F8FA] px-2 py-0.5 text-[11px] font-medium text-[#525960]">{{ label(item.kind) }}</span>
                                </p>
                                <p class="text-xs text-[#525960]">
                                    {{ [item.code, item.brand, item.model, item.space?.name].filter(Boolean).join(' · ') }}
                                </p>
                                <p v-if="item.next_maintenance_at" :class="['text-xs', maintenanceDue(item) ? 'font-semibold text-[#941B2B]' : 'text-[#858B93]']">
                                    Próximo mantenimiento: {{ formatDate(item.next_maintenance_at) }}
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-1">
                                <StatusBadge :value="item.status" />
                                <button
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#383D43] hover:bg-[#F9ECEE] hover:text-[#941B2B]"
                                    title="Editar"
                                    @click="openRecord('equipment', item.id)"
                                >
                                    <Pencil class="h-4 w-4" />
                                </button>
                                <Link
                                    :href="admin.clinicIncidents.create({ query: { clinic_id: clinic.id, clinic_equipment_id: item.id } }).url"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F9ECEE]"
                                    title="Reportar incidencia"
                                >
                                    <TriangleAlert class="h-4 w-4" />
                                </Link>
                            </div>
                        </li>
                    </ul>

                    <p v-else class="text-sm italic text-[#858B93]">Sin equipos registrados.</p>

                    <template #footer>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#941B2B] hover:underline"
                            @click="openRecord('equipment')"
                        >
                            <Plus class="h-4 w-4" /> Agregar equipo
                        </button>
                    </template>
                </AdminCard>
            </div>

            <div class="flex flex-col gap-5">
                <!-- Incidencias abiertas -->
                <AdminCard title="Incidencias abiertas" :icon="TriangleAlert">
                    <ul v-if="openIncidents.length" class="flex flex-col gap-3">
                        <li v-for="incident in openIncidents" :key="incident.id">
                            <Link :href="admin.clinicIncidents.show(incident.id).url" class="group flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-[#292D32] group-hover:text-[#941B2B]">{{ incident.title }}</p>
                                    <p class="text-xs text-[#858B93]">{{ label(incident.type) }} · {{ formatDate(incident.occurred_at) }}</p>
                                </div>
                                <SeverityBadge :value="incident.severity" />
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="text-sm italic text-[#858B93]">Sin incidencias pendientes.</p>

                    <template #footer>
                        <Link
                            :href="admin.clinicIncidents.create({ query: { clinic_id: clinic.id } }).url"
                            class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#941B2B] hover:underline"
                        >
                            <Plus class="h-4 w-4" /> Reportar incidencia
                        </Link>
                    </template>
                </AdminCard>

                <RecordList
                    title="Supervisores"
                    :icon="UserRoundCheck"
                    :items="clinic.supervisors.map((s) => ({
                        id: s.id,
                        title: fullName(s.teacher?.people),
                        subtitle: [label(s.role), s.area?.name].filter(Boolean).join(' · '),
                        meta: s.starts_at || s.ends_at ? `${formatDate(s.starts_at)} – ${s.ends_at ? formatDate(s.ends_at) : 'vigente'}` : undefined,
                    }))"
                    empty-text="Sin supervisores asignados."
                    add-label="Asignar supervisor"
                    @add="openRecord('supervisors')"
                    @edit="(id) => openRecord('supervisors', id)"
                    @remove="(id) => removeRecord('supervisors', id)"
                />

                <RecordList
                    title="Áreas"
                    :icon="LayoutGrid"
                    :items="clinic.areas.map((a) => ({
                        id: a.id,
                        title: a.name,
                        subtitle: [a.specialty?.name, a.description].filter(Boolean).join(' · ') || undefined,
                        meta: a.is_active ? undefined : 'Inactiva',
                    }))"
                    empty-text="Sin áreas registradas."
                    add-label="Agregar área"
                    @add="openRecord('areas')"
                    @edit="(id) => openRecord('areas', id)"
                    @remove="(id) => removeRecord('areas', id)"
                />
            </div>
        </div>

        <!-- Diálogo de registros -->
        <FormDialog
            v-model:open="dialogOpen"
            :title="`${editingId ? 'Editar' : 'Agregar'} ${TITLES[recordType]}`"
            :loading="recordForm.processing"
            @submit="submitRecord"
        >
            <template v-if="recordType === 'areas'">
                <FormInput id="area_name" v-model="recordForm.name" label="Nombre" placeholder="Ej. Endodoncia" :error="recordForm.errors.name" required />
                <FormSelect id="area_specialty" v-model="recordForm.specialty_id" label="Especialidad" placeholder="General" :options="recordOptions(specialties)" :error="recordForm.errors.specialty_id" nullable />
                <div class="md:col-span-2">
                    <FormTextarea id="area_description" v-model="recordForm.description" label="Descripción" :rows="3" :error="recordForm.errors.description" />
                </div>
                <div class="md:col-span-2">
                    <ActiveSwitch v-model="recordForm.is_active" label="Área activa" :error="recordForm.errors.is_active" />
                </div>
            </template>

            <template v-else-if="recordType === 'supervisors'">
                <div class="md:col-span-2">
                    <FormSelect id="sup_teacher" v-model="recordForm.teacher_profile_id" label="Docente" :options="teacherOptions" :error="recordForm.errors.teacher_profile_id" required />
                </div>
                <FormSelect id="sup_role" v-model="recordForm.role" label="Función" :options="enumOptions(supervisorRoles)" :error="recordForm.errors.role" required />
                <FormSelect id="sup_area" v-model="recordForm.clinic_area_id" label="Área" placeholder="Toda la clínica" :options="areaOptions" :error="recordForm.errors.clinic_area_id" nullable />
                <FormInput id="sup_starts" v-model="recordForm.starts_at" type="date" label="Desde" :error="recordForm.errors.starts_at" />
                <FormInput id="sup_ends" v-model="recordForm.ends_at" type="date" label="Hasta" help="Vacío si sigue vigente." :error="recordForm.errors.ends_at" />
            </template>

            <template v-else>
                <FormSelect id="eq_kind" v-model="recordForm.kind" label="Tipo" :options="enumOptions(equipmentKinds)" :error="recordForm.errors.kind" required />
                <FormInput id="eq_name" v-model="recordForm.name" label="Nombre" :placeholder="recordForm.kind === 'instrumental' ? 'Ej. Kit de fórceps' : 'Ej. Unidad dental'" :error="recordForm.errors.name" required />
                <FormInput id="eq_code" v-model="recordForm.code" label="No. de inventario" :error="recordForm.errors.code" required />
                <FormInput id="eq_brand" v-model="recordForm.brand" label="Marca" :error="recordForm.errors.brand" />
                <FormInput id="eq_model" v-model="recordForm.model" label="Modelo" :error="recordForm.errors.model" />
                <FormInput id="eq_serial" v-model="recordForm.serial_number" label="No. de serie" :error="recordForm.errors.serial_number" />
                <FormSelect id="eq_space" v-model="recordForm.space_id" label="Consultorio" placeholder="Sin asignar" :options="spaceOptions" :error="recordForm.errors.space_id" nullable />
                <FormSelect id="eq_status" v-model="recordForm.status" label="Estado" :options="enumOptions(equipmentStatuses)" :error="recordForm.errors.status" required />
                <FormInput id="eq_acquired" v-model="recordForm.acquired_at" type="date" label="Fecha de adquisición" :error="recordForm.errors.acquired_at" />
                <FormInput id="eq_last" v-model="recordForm.last_maintenance_at" type="date" label="Último mantenimiento" :error="recordForm.errors.last_maintenance_at" />
                <FormInput id="eq_next" v-model="recordForm.next_maintenance_at" type="date" label="Próximo mantenimiento" :error="recordForm.errors.next_maintenance_at" />
                <div class="md:col-span-2">
                    <FormTextarea id="eq_notes" v-model="recordForm.notes" label="Notas" :rows="2" :error="recordForm.errors.notes" />
                </div>
                <div v-if="editingId" class="md:col-span-2">
                    <button type="button" class="text-sm font-semibold text-[#941B2B] hover:underline" @click="dialogOpen = false; removeRecord('equipment', editingId)">
                        Eliminar este equipo
                    </button>
                </div>
            </template>
        </FormDialog>
    </div>
</template>
