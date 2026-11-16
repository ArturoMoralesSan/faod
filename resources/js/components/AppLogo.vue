
<script setup lang="ts">
const logoUrl =
    'https://upload.wikimedia.org/wikipedia/commons/0/0d/UJED_LOGO_2024.png'
</script>

<template>
    <div class="flex w-full items-center gap-3 overflow-hidden">
        <img
            :src="logoUrl"
            alt="Universidad Juárez del Estado de Durango"
            class="size-11 shrink-0 rounded-md object-contain"
        />

        <div class="flex min-w-0 flex-col leading-tight">
            <span class="truncate text-[11px] font-extrabold tracking-wide text-[#991B2B]">
                FACULTAD DE ODONTOLOGÍA
            </span>

            <span class="mt-1 text-[10px] font-medium leading-tight text-sidebar-foreground/70">
                Sistema Integral de
                <br />
                Gestión Interna
            </span>
        </div>
    </div>
</template>
