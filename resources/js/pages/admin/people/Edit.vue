<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Users } from 'lucide-vue-next';

import FormActions from '@/components/admin/FormActions.vue';
import PeopleForm from '@/components/admin/forms/PeopleForm.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { fullName } from '@/lib/admin';
import { peopleFormDefaults, peoplePayload } from '@/lib/people';
import admin from '@/routes/admin';
import type { Option, PersonName } from '@/types/admin';
import type { PeopleFormData } from '@/types/forms';

const props = defineProps<{
    person: PersonName & {
        id: number;
        user: { id: number; email: string } | null;
        [key: string]: unknown;
    };
    departments: Option[];
}>();

const form = useForm<PeopleFormData>(peopleFormDefaults(props.person));

const submit = (): void => {
    form.transform((data) => peoplePayload(data, { keepAccountWhenOff: true }))
        .put(admin.people.update(props.person.id).url);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel', href: admin.dashboard() },
            { title: 'Personas', href: admin.people.index() },
            { title: 'Editar', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar persona" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#F2F7F4] p-4 sm:p-5">
        <PageHeader
            title="Editar persona"
            :description="fullName(person)"
            section="Comunidad"
            :icon="Users"
            :action-icon="ArrowLeft"
            action-label="Volver a la persona"
            :action-href="admin.people.show(person.id).url"
        />

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <PeopleForm
                :form="form"
                :departments="departments"
                :has-existing-account="Boolean(person.user)"
                is-edit
            />

            <FormActions :cancel-href="admin.people.show(person.id).url" :loading="form.processing" />
        </form>
    </div>
</template>
