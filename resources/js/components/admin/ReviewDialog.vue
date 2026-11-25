<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

import FormDialog from '@/components/admin/FormDialog.vue';
import FormTextarea from '@/components/admin/FormTextarea.vue';
import { label } from '@/lib/admin';
import admin from '@/routes/admin';

/**
 * Revisión docente de un registro clínico (diagnóstico, plan de tratamiento…).
 */
const props = defineProps<{
    reviewableType: string;
    reviewableId: number | null;
    title: string;
    decisions: string[];
}>();

const open = defineModel<boolean>('open', { required: true });

const form = useForm({
    reviewable_type: props.reviewableType,
    reviewable_id: props.reviewableId,
    decision: 'autorizado',
    comments: '',
});

watch(open, (isOpen) => {
    if (isOpen) {
        form.reset();
        form.clearErrors();
        form.reviewable_type = props.reviewableType;
        form.reviewable_id = props.reviewableId;
    }
});

const TONES: Record<string, string> = {
    autorizado: 'border-[#187744] bg-[#E2F3E8] text-[#187744]',
    correcciones: 'border-[#8A5A00] bg-[#FDF6E7] text-[#8A5A00]',
    rechazado: 'border-[#941B2B] bg-[#F9ECEE] text-[#941B2B]',
};

const submit = (): void => {
    form.transform((data) => ({ ...data, comments: data.comments || null }))
        .post(admin.reviews.store().url, { preserveScroll: true, onSuccess: () => (open.value = false) });
};
</script>

<template>
    <FormDialog v-model:open="open" :title="`Revisar: ${title}`" submit-label="Registrar revisión" :loading="form.processing" @submit="submit">
        <div class="md:col-span-2">
            <p class="form-label mb-2">Decisión</p>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                <button
                    v-for="decision in decisions"
                    :key="decision"
                    type="button"
                    :class="[
                        'rounded-xl border px-3 py-2.5 text-sm font-semibold transition',
                        form.decision === decision ? TONES[decision] : 'border-[#E3E5E8] bg-white text-[#525960] hover:border-[#B8BEC6]',
                    ]"
                    @click="form.decision = decision"
                >
                    {{ label(decision) }}
                </button>
            </div>
            <p v-if="form.errors.decision" class="form-error">{{ form.errors.decision }}</p>
        </div>

        <div class="md:col-span-2">
            <FormTextarea
                id="review_comments"
                v-model="form.comments"
                label="Comentarios"
                :rows="3"
                :required="form.decision !== 'autorizado'"
                :error="form.errors.comments"
            />
        </div>
    </FormDialog>
</template>
