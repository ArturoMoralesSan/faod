<script setup lang="ts">
import { Printer } from 'lucide-vue-next';

/** Enlace con estilo de botón secundario que abre un PDF en otra pestaña. */
defineProps<{ href: string; label?: string }>();
</script>

<template>
    <a
        :href="href"
        target="_blank"
        rel="noopener"
        class="inline-flex min-h-12 items-center justify-center gap-2.5 rounded-full border border-[#E3E5E8] bg-white px-[23px] font-[DM_Sans,sans-serif] text-[13px] font-semibold text-[#383D43] transition-[transform,background-color,color,border-color] duration-[250ms] ease-in-out hover:-translate-y-0.5 hover:border-[#B8BEC6] hover:bg-[#F1F2F4] hover:text-[#292D32] focus-visible:outline focus-visible:outline-[3px] focus-visible:outline-offset-2 focus-visible:outline-[#E8C7CC] motion-reduce:transition-none"
    >
        <Printer class="h-4 w-4" aria-hidden="true" />
        {{ label ?? 'Imprimir horario' }}
    </a>
</template>
