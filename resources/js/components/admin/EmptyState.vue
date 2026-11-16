<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    icon: Component;
    title: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-3 py-8">
        <div
            class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]"
        >
            <component :is="icon" class="h-6 w-6" />
        </div>

        <div class="text-center">
            <p class="font-semibold text-[#292D32]">
                {{ title }}
            </p>

            <p v-if="description" class="mt-1 text-sm text-[#737982]">
                {{ description }}
            </p>
        </div>

        <slot />
    </div>
</template>
