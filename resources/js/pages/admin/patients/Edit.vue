<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Contact } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import PatientForm from '@/components/admin/forms/PatientForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { fullName, toDateInput } from '@/lib/admin';
import admin from '@/routes/admin';
import type { PersonName } from '@/types/admin';
import type { PatientFormData, PatientFormOptions } from '@/types/forms';


interface PatientRecord {
    id: number;
    record_number: string;
    clinic_id: number | null;
    blood_type: string | null;
    occupation: string | null;
    marital_status: string | null;
    referred_by: string | null;
    status: string;
    registered_at: string;
    notes: string | null;
    people: PersonName & Record<string, string | null | number>;
}

const props = defineProps<PatientFormOptions & { patient: PatientRecord }>();

const { patient, ...options } = props;
const person = patient.people as unknown as Record<string, string | null>;
const text = (value: string | null | undefined) => value ?? '';

const form = useForm<PatientFormData>({
    curp: text(person.curp),
    first_name: text(person.first_name),
    middle_name: text(person.middle_name),
    paternal_surname: text(person.paternal_surname),
    maternal_surname: text(person.maternal_surname),
    birth_date: toDateInput(person.birth_date),
    gender: text(person.gender),
    phone: text(person.phone),
    personal_email: text(person.personal_email),
    street: text(person.street),
    exterior_number: text(person.exterior_number),
    interior_number: text(person.interior_number),
    neighborhood: text(person.neighborhood),
    postal_code: text(person.postal_code),
    city: text(person.city),
    state: text(person.state),
    clinic_id: patient.clinic_id,
    blood_type: text(patient.blood_type),
    occupation: text(patient.occupation),
    marital_status: text(patient.marital_status),
    referred_by: text(patient.referred_by),
    status: text(patient.status),
    registered_at: toDateInput(patient.registered_at),
    notes: text(patient.notes),
});

const submit = (): void => {
    form.transform((data) => Object.fromEntries(Object.entries(data).map(([k, v]) => [k, v === '' ? null : v])))
        .put(admin.patients.update(patient.id).url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Pacientes', href: admin.patients.index() },
            { title: 'Editar', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar paciente" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Editar paciente"
            :description="`${patient.record_number} · ${fullName(patient.people)}`"
            section="Atención clínica"
            :icon="Contact"
            :action-icon="ArrowLeft"
            action-label="Volver al expediente"
            :action-href="admin.patients.show(patient.id).url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <PatientForm :form="form" v-bind="options" is-edit />

            <FormActions :cancel-href="admin.patients.show(patient.id).url" :loading="form.processing" />
        </form>
    </div>
</template>
