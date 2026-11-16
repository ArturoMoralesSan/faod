<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Users } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import PeopleForm from '@/components/admin/forms/PeopleForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { peopleFormDefaults, peoplePayload } from '@/lib/people';
import admin from '@/routes/admin';
import type { Option } from '@/types/admin';
import type { PeopleFormData } from '@/types/forms';

defineProps<{
    departments: Option[];
}>();

// Permite llegar desde "Registrar docente" con ?profile=teacher.
const params = new URLSearchParams(usePage().url.split('?')[1] ?? '');
const defaults = peopleFormDefaults();

defaults.has_student_profile = params.get('profile') === 'student';
defaults.has_teacher_profile = params.get('profile') === 'teacher';
defaults.has_staff_profile = params.get('profile') === 'staff';

const form = useForm<PeopleFormData>(defaults);

const submit = (): void => {
    form.transform((data) => peoplePayload(data)).post(admin.people.store().url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Personas', href: admin.people.index() },
            { title: 'Nueva', href: admin.people.create() },
        ],
    },
});
</script>

<template>
    <Head title="Nueva persona" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Nueva persona"
            description="Registra a un alumno, docente o administrativo; una misma persona puede tener varios perfiles."
            section="Comunidad"
            :icon="Users"
            :action-icon="ArrowLeft"
            action-label="Volver a personas"
            :action-href="admin.people.index().url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <PeopleForm :form="form" :departments="departments" />

            <FormActions
                :cancel-href="admin.people.index().url"
                submit-label="Registrar persona"
                loading-text="Registrando..."
                :loading="form.processing"
            />
        </form>
    </div>
</template>
