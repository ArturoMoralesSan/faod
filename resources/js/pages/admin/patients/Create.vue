<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Contact } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import PatientForm from '@/components/admin/forms/PatientForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { todayInput } from '@/lib/admin';
import admin from '@/routes/admin';
import type { PatientFormData, PatientFormOptions } from '@/types/forms';

const props = defineProps<PatientFormOptions>();

const form = useForm<PatientFormData>({
    curp: '', first_name: '', middle_name: '', paternal_surname: '', maternal_surname: '',
    birth_date: '', gender: '', phone: '', personal_email: '',
    street: '', exterior_number: '', interior_number: '', neighborhood: '', postal_code: '', city: '', state: '',
    clinic_id: props.clinics.length === 1 ? props.clinics[0].id : null,
    blood_type: '', occupation: '', marital_status: '', referred_by: '',
    status: 'activo', registered_at: todayInput(), notes: '',
});

const submit = (): void => {
    form.transform((data) => Object.fromEntries(Object.entries(data).map(([k, v]) => [k, v === '' ? null : v])))
        .post(admin.patients.store().url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Pacientes', href: admin.patients.index() },
            { title: 'Nuevo', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo paciente" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Nuevo paciente"
            description="Abre el expediente clínico. Antecedentes, alergias y consultas se agregan después desde el expediente."
            section="Atención clínica"
            :icon="Contact"
            :action-icon="ArrowLeft"
            action-label="Volver a pacientes"
            :action-href="admin.patients.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <PatientForm :form="form" v-bind="props" />

            <FormActions
                :cancel-href="admin.patients.index().url"
                submit-label="Abrir expediente"
                loading-text="Guardando..."
                :loading="form.processing"
            />
        </form>
    </div>
</template>
