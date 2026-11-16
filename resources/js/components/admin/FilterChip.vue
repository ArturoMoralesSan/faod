<script setup lang="ts">
import { FilterX } from 'lucide-vue-next';
</script>

<template>
    <span
        class="inline-flex items-center gap-2 rounded-full border border-[#E8C7CC] bg-[#F9ECEE] px-3 py-1.5 text-xs font-medium text-[#941B2B]"
    >
        <FilterX class="h-3.5 w-3.5" />
        <slot />
    </span>
</template>
