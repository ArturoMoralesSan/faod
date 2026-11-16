<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Download, FileText, Paperclip, Trash2, Upload } from 'lucide-vue-next';
import { ref } from 'vue';

import AdminButton from '@/components/admin/AdminButton.vue';
import AdminCard from '@/components/admin/AdminCard.vue';
import { confirmDelete, formatDate, formatSize } from '@/lib/admin';
import admin from '@/routes/admin';
import type { Attachment } from '@/types/admin';

/**
 * Adjuntos de un registro (comprobantes, documentos, materiales).
 * attachableType debe existir en Attachment::ATTACHABLE_TYPES.
 */
const props = withDefaults(
    defineProps<{
        attachableType: string;
        attachableId: number;
        attachments: Attachment[];
        title?: string;
        description?: string;
    }>(),
    {
        title: 'Documentos',
        description: 'Archivos PDF, imágenes u hojas de cálculo (máx. 20 MB).',
    },
);

const fileInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    attachable_type: props.attachableType,
    attachable_id: props.attachableId,
    title: '',
    file: null as File | null,
});

const onFileSelected = (event: Event): void => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    if (!file) {
        return;
    }

    form.file = file;
    form.title = form.title || file.name;

    form.post(admin.attachments.store().url, {
        preserveScroll: true,
        forceFormData: true,
        onFinish: () => {
            form.reset('file', 'title');

            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
};

const remove = (attachment: Attachment): void => {
    confirmDelete(
        '¿Eliminar archivo?',
        `Se eliminará "${attachment.title}". Esta acción no se puede deshacer.`,
        admin.attachments.destroy(attachment.id).url,
        { preserveState: true },
    );
};
</script>

<template>
    <AdminCard :title="title" :description="description" :icon="Paperclip">
        <ul v-if="attachments.length" class="mb-4 divide-y divide-[#E3E5E8] rounded-xl border border-[#E3E5E8]">
            <li
                v-for="attachment in attachments"
                :key="attachment.id"
                class="flex items-center gap-3 px-4 py-3"
            >
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#F9ECEE] text-[#941B2B]">
                    <FileText class="h-4 w-4" />
                </div>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-[#292D32]">
                        {{ attachment.title }}
                    </p>

                    <p class="text-xs text-[#858B93]">
                        {{ formatSize(attachment.size) }} · {{ formatDate(attachment.created_at) }}
                    </p>
                </div>

                <a
                    :href="admin.attachments.download(attachment.id).url"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-[#941B2B] transition-colors hover:bg-[#F9ECEE]"
                    title="Descargar"
                >
                    <Download class="h-4 w-4" />
                </a>

                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-[#941B2B] transition-colors hover:bg-[#F9ECEE]"
                    title="Eliminar"
                    @click="remove(attachment)"
                >
                    <Trash2 class="h-4 w-4" />
                </button>
            </li>
        </ul>

        <p v-else class="mb-4 text-sm italic text-[#858B93]">
            Todavía no hay archivos adjuntos.
        </p>

        <input
            ref="fileInput"
            type="file"
            class="hidden"
            accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"
            @change="onFileSelected"
        />

        <AdminButton
            variant="secondary"
            :icon="Upload"
            :loading="form.processing"
            loading-text="Subiendo..."
            @click="fileInput?.click()"
        >
            Adjuntar archivo
        </AdminButton>

        <p v-if="form.errors.file" class="form-error">
            {{ form.errors.file }}
        </p>
    </AdminCard>
</template>
