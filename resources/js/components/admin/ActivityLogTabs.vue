<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FileClock, LogIn } from 'lucide-vue-next';

import admin from '@/routes/admin';

/** Pestañas: historial de cambios y bitácora de accesos. */
defineProps<{ active: 'changes' | 'logins' }>();
</script>

<template>
    <nav class="inline-flex w-fit rounded-xl border border-[#E3E5E8] bg-white p-1 text-sm font-semibold" aria-label="Historial">
        <Link
            :href="admin.activityLog.index().url"
            :class="['inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5', active === 'changes' ? 'bg-[#941B2B] text-white' : 'text-[#525960] hover:text-[#941B2B]']"
            :aria-current="active === 'changes' ? 'page' : undefined"
        >
            <FileClock class="h-4 w-4" /> Cambios a registros
        </Link>
        <Link
            :href="admin.activityLog.logins().url"
            :class="['inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5', active === 'logins' ? 'bg-[#941B2B] text-white' : 'text-[#525960] hover:text-[#941B2B]']"
            :aria-current="active === 'logins' ? 'page' : undefined"
        >
            <LogIn class="h-4 w-4" /> Accesos al sistema
        </Link>
    </nav>
</template>
