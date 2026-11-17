<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { ClipboardList, Fingerprint, Mail, MapPin, Phone, UserRound } from 'lucide-vue-next';

import AdminCard from '@/components/admin/AdminCard.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import { enumOptions, recordOptions } from '@/lib/admin';
import type { PatientFormData, PatientFormOptions } from '@/types/forms';

defineProps<{
    form: InertiaForm<PatientFormData>;
    isEdit?: boolean;
} & PatientFormOptions>();

const genders = ['femenino', 'masculino', 'no_binario', 'prefiero_no_decir'];
</script>

<template>
    <AdminCard
        title="Datos personales"
        :description="isEdit ? undefined : 'Si la CURP ya está registrada (p. ej. un alumno), el expediente se liga a esa persona.'"
        :icon="UserRound"
    >
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
            <FormInput id="first_name" v-model="form.first_name" label="Nombre(s)" :error="form.errors.first_name" required />
            <FormInput id="middle_name" v-model="form.middle_name" label="Segundo nombre" :error="form.errors.middle_name" />
            <FormInput id="paternal_surname" v-model="form.paternal_surname" label="Apellido paterno" :error="form.errors.paternal_surname" required />
            <FormInput id="maternal_surname" v-model="form.maternal_surname" label="Apellido materno" :error="form.errors.maternal_surname" />

            <FormInput id="curp" v-model="form.curp" label="CURP" :icon="Fingerprint" :maxlength="18" :error="form.errors.curp" />
            <FormInput id="birth_date" v-model="form.birth_date" type="date" label="Fecha de nacimiento" :error="form.errors.birth_date" />
            <FormSelect id="gender" v-model="form.gender" label="Sexo" placeholder="Sin especificar" :options="enumOptions(genders)" :error="form.errors.gender" />
            <FormSelect id="marital_status" v-model="form.marital_status" label="Estado civil" placeholder="Sin especificar" :options="enumOptions(maritalStatuses)" :error="form.errors.marital_status" />

            <FormInput id="occupation" v-model="form.occupation" label="Ocupación" :error="form.errors.occupation" />
            <FormSelect id="blood_type" v-model="form.blood_type" label="Tipo de sangre" placeholder="Desconocido" :options="bloodTypes.map((t) => ({ value: t, label: t }))" :error="form.errors.blood_type" />
        </div>
    </AdminCard>

    <AdminCard title="Contacto y domicilio" :icon="MapPin">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
            <FormInput id="phone" v-model="form.phone" type="tel" label="Teléfono" :icon="Phone" :error="form.errors.phone" />
            <div class="xl:col-span-3">
                <FormInput id="personal_email" v-model="form.personal_email" type="email" label="Correo" :icon="Mail" :error="form.errors.personal_email" />
            </div>

            <div class="md:col-span-2">
                <FormInput id="street" v-model="form.street" label="Calle" :error="form.errors.street" />
            </div>
            <FormInput id="exterior_number" v-model="form.exterior_number" label="No. exterior" :error="form.errors.exterior_number" />
            <FormInput id="interior_number" v-model="form.interior_number" label="No. interior" :error="form.errors.interior_number" />

            <FormInput id="neighborhood" v-model="form.neighborhood" label="Colonia" :error="form.errors.neighborhood" />
            <FormInput id="postal_code" v-model="form.postal_code" label="Código postal" :maxlength="10" :error="form.errors.postal_code" />
            <FormInput id="city" v-model="form.city" label="Ciudad" :error="form.errors.city" />
            <FormInput id="state" v-model="form.state" label="Estado" :error="form.errors.state" />
        </div>
    </AdminCard>

    <AdminCard title="Expediente" :icon="ClipboardList">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
            <FormInput id="registered_at" v-model="form.registered_at" type="date" label="Fecha de apertura" :error="form.errors.registered_at" required />
            <FormSelect id="clinic_id" v-model="form.clinic_id" label="Clínica de primera atención" placeholder="Sin especificar" :options="recordOptions(clinics)" :error="form.errors.clinic_id" nullable />
            <FormSelect id="status" v-model="form.status" label="Estado" :options="enumOptions(statuses)" :error="form.errors.status" required />
            <FormInput id="referred_by" v-model="form.referred_by" label="Referido por" :error="form.errors.referred_by" />

            <div class="md:col-span-2 xl:col-span-4">
                <FormTextarea id="notes" v-model="form.notes" label="Observaciones" :rows="3" :error="form.errors.notes" />
            </div>
        </div>
    </AdminCard>
</template>
