<script setup lang="ts">
import type { Component } from 'vue';

withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        icon?: Component;
        padding?: boolean;
    }>(),
    {
        title: '',
        description: '',
        padding: true,
    },
);
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border border-[#E3E5E8] bg-white font-['DM_Sans',sans-serif]"
    >
        <!-- Encabezado opcional -->
        <div
            v-if="title || description || icon || $slots.header"
            class="border-b border-[#E3E5E8] px-5 py-4 sm:px-6"
        >
            <slot name="header">
                <div class="flex items-start gap-3">
                    <div
                        v-if="icon"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]"
                    >
                        <component
                            :is="icon"
                            :size="20"
                            :stroke-width="2"
                            aria-hidden="true"
                        />
                    </div>

                    <div class="min-w-0">
                        <h2
                            v-if="title"
                            class="font-['Manrope',sans-serif] text-base font-semibold tracking-[-0.02em] text-[#941B2B]"
                        >
                            {{ title }}
                        </h2>

                        <p
                            v-if="description"
                            class="mt-1 text-sm leading-5 text-[#737982]"
                        >
                            {{ description }}
                        </p>
                    </div>
                </div>
            </slot>
        </div>

        <!-- Contenido -->
        <div :class="padding ? 'p-5 sm:p-6' : ''">
            <slot />
        </div>

        <!-- Pie opcional -->
        <div
            v-if="$slots.footer"
            class="flex flex-col gap-3 border-t border-[#E3E5E8] bg-[#F7F8FA] px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6"
        >
            <slot name="footer" />
        </div>
    </section>
</template>