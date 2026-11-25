<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Camera, LoaderCircle, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import { confirmDelete } from '@/lib/admin';
import admin from '@/routes/admin';

/**
 * Fotografía de una persona con opción de subirla o quitarla.
 */
const props = withDefaults(
    defineProps<{
        personId: number;
        photoPath: string | null;
        name: string;
        editable?: boolean;
        size?: 'md' | 'lg';
    }>(),
    { editable: true, size: 'lg' },
);

const input = ref<HTMLInputElement | null>(null);
const uploading = ref(false);
const error = ref('');

// photo_path cambia con cada foto nueva, así el navegador no muestra la anterior.
const src = computed(() => (props.photoPath ? `${admin.people.photo.show(props.personId).url}?v=${encodeURIComponent(props.photoPath)}` : null));

const initials = computed(() =>
    props.name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0]?.toUpperCase())
        .join(''),
);

const upload = (event: Event): void => {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    error.value = '';
    router.post(admin.people.photo.update(props.personId).url, { photo: file }, {
        forceFormData: true,
        preserveScroll: true,
        onStart: () => (uploading.value = true),
        onFinish: () => {
            uploading.value = false;

            if (input.value) {
                input.value.value = '';
            }
        },
        onError: (errors) => (error.value = errors.photo ?? 'No se pudo subir la fotografía.'),
    });
};

const remove = (): void => {
    confirmDelete('¿Quitar fotografía?', `Se eliminará la fotografía de ${props.name}.`, admin.people.photo.destroy(props.personId).url, { preserveState: true });
};
</script>

<template>
    <div class="flex flex-col items-center gap-2">
        <div
            class="relative overflow-hidden rounded-2xl border border-[#E3E5E8] bg-[#F9ECEE]"
            :class="size === 'lg' ? 'h-36 w-28' : 'h-20 w-16'"
        >
            <img v-if="src" :src="src" :alt="`Fotografía de ${name}`" class="h-full w-full object-cover" />
            <span v-else class="flex h-full w-full items-center justify-center font-['Manrope',sans-serif] font-bold text-[#941B2B]" :class="size === 'lg' ? 'text-3xl' : 'text-lg'">
                {{ initials }}
            </span>
            <span v-if="uploading" class="absolute inset-0 flex items-center justify-center bg-white/70">
                <LoaderCircle class="h-6 w-6 animate-spin text-[#941B2B]" />
            </span>
        </div>

        <div v-if="editable" class="flex items-center gap-1">
            <button
                type="button"
                class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold text-[#941B2B] hover:bg-[#F1F2F4]"
                :disabled="uploading"
                @click="input?.click()"
            >
                <Camera class="h-3.5 w-3.5" /> {{ photoPath ? 'Cambiar' : 'Subir foto' }}
            </button>
            <button v-if="photoPath" type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-[#858B93] hover:bg-[#F1F2F4] hover:text-[#292D32]" title="Quitar fotografía" @click="remove">
                <Trash2 class="h-3.5 w-3.5" />
            </button>
            <input ref="input" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="upload" />
        </div>
        <p v-if="error" class="max-w-[10rem] text-center text-xs text-[#941B2B]">{{ error }}</p>
    </div>
</template>
