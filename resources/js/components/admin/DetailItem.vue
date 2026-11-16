<script setup lang="ts">
defineProps<{
    label: string;
    value?: string | number | null;
}>();
</script>

<template>
    <div class="min-w-0">
        <dt class="text-xs font-medium uppercase tracking-wide text-[#858B93]">
            {{ label }}
        </dt>

        <dd class="mt-1 break-words text-sm font-medium text-[#292D32]">
            <slot>{{ value ?? '—' }}</slot>
        </dd>
    </div>
</template>
