<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Mail, MapPin, Phone, ShieldCheck } from 'lucide-vue-next';

import { IMAGES, INSTITUTION } from '@/lib/institution';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const year = new Date().getFullYear();
</script>

<template>
    <div class="relative grid min-h-svh bg-[#F7F8FA] font-['DM_Sans',sans-serif] lg:grid-cols-[1.1fr_1fr]">
        <!-- Fachada -->
        <aside class="relative hidden min-h-svh flex-col justify-between overflow-hidden bg-[#5E0F1B] p-10 text-white lg:flex xl:p-14">
            <img
                :src="IMAGES.facade"
                alt="Fachada de la Facultad de Odontología de la UJED"
                class="absolute inset-0 h-full w-full object-cover"
                :style="{ objectPosition: IMAGES.facadePosition }"
            />
            <!-- Guinda institucional sobre la fotografía: más intensa arriba y abajo para leer el texto. -->
            <div class="absolute inset-0 bg-gradient-to-b from-[#5E0F1B]/90 via-[#941B2B]/45 to-[#3D0912]/95" />

            <Link :href="home()" class="relative z-10 flex w-fit items-center gap-4">
                <img :src="IMAGES.logoUjedWhite" :alt="INSTITUTION.university" class="h-16 w-auto" />
                <span class="border-l border-white/30 pl-4">
                    <span class="block text-[11px] font-semibold uppercase tracking-[0.18em] text-white/75">{{ INSTITUTION.university }}</span>
                    <span class="block font-['Manrope',sans-serif] text-lg font-bold leading-tight">{{ INSTITUTION.name }}</span>
                </span>
            </Link>

            <div class="relative z-10 max-w-lg">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3.5 py-1.5 text-[11px] font-semibold uppercase tracking-[0.14em] backdrop-blur-sm">
                    <span class="size-1.5 rounded-full bg-[#F2C14E]" /> {{ INSTITUTION.system }}
                </span>
                <h2 class="mt-6 font-['Manrope',sans-serif] text-4xl font-extrabold leading-[1.1] tracking-tight xl:text-5xl">
                    Academia, clínica y administración
                    <span class="block text-[#F6D6DB]">en un solo lugar.</span>
                </h2>
                <p class="mt-5 max-w-md text-[15px] leading-7 text-white/80">
                    Alumnos, docentes, pacientes, agenda, inventario y finanzas de la facultad, con acceso según tu rol.
                </p>
            </div>

            <div class="relative z-10 grid gap-2 border-t border-white/15 pt-5 text-xs text-white/75">
                <span class="inline-flex items-center gap-2"><MapPin class="size-3.5 shrink-0" /> {{ INSTITUTION.address }}</span>
                <span class="flex flex-wrap gap-x-5 gap-y-2">
                    <a :href="INSTITUTION.phoneHref" class="inline-flex items-center gap-2 hover:text-white"><Phone class="size-3.5" /> {{ INSTITUTION.phone }}</a>
                    <a :href="`mailto:${INSTITUTION.email}`" class="inline-flex items-center gap-2 hover:text-white"><Mail class="size-3.5" /> {{ INSTITUTION.email }}</a>
                </span>
            </div>
        </aside>

        <!-- Formulario -->
        <main class="flex min-h-svh flex-col items-center justify-center px-5 py-10 sm:px-8 lg:px-12">
            <div class="w-full max-w-[420px]">
                <!-- Identidad en móvil (la fachada se oculta) -->
                <Link :href="home()" class="mb-8 flex items-center gap-3 lg:hidden">
                    <span class="flex size-12 items-center justify-center rounded-xl bg-[#941B2B] p-1.5">
                        <img :src="IMAGES.logoUjedWhite" :alt="INSTITUTION.university" class="h-full w-auto" />
                    </span>
                    <span>
                        <span class="block text-[10px] font-semibold uppercase tracking-[0.16em] text-[#941B2B]">{{ INSTITUTION.university }}</span>
                        <span class="block font-['Manrope',sans-serif] text-base font-bold text-[#292D32]">{{ INSTITUTION.name }}</span>
                    </span>
                </Link>

                <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#941B2B]">{{ INSTITUTION.short }} · {{ INSTITUTION.universityShort }}</p>
                <h1 v-if="title" class="mt-2 font-['Manrope',sans-serif] text-[28px] font-extrabold leading-tight tracking-tight text-[#1F2226]">{{ title }}</h1>
                <p v-if="description" class="mt-2 text-sm leading-6 text-[#737982]">{{ description }}</p>

                <div class="auth-form mt-8">
                    <slot />
                </div>

                <p class="mt-8 flex items-center gap-2 text-xs text-[#858B93]">
                    <ShieldCheck class="size-4 shrink-0 text-[#941B2B]" />
                    Acceso exclusivo para personal, docentes y alumnos de la facultad.
                </p>
                <p class="mt-6 border-t border-[#E3E5E8] pt-5 text-xs text-[#858B93]">
                    © {{ year }} {{ INSTITUTION.name }} · {{ INSTITUTION.university }}
                </p>
            </div>
        </main>
    </div>
</template>

<style>
/* Formularios de acceso con los colores de la facultad. */
.auth-form label {
    color: #383d43;
    font-weight: 600;
}

.auth-form input[type='email'],
.auth-form input[type='password'],
.auth-form input[type='text'] {
    height: 2.75rem;
    border-radius: 0.75rem;
    border-color: #e3e5e8;
    background: #ffffff;
}

.auth-form input:focus-visible {
    border-color: #941b2b;
    box-shadow: 0 0 0 3px rgb(148 27 43 / 0.12);
}

.auth-form [type='submit'] {
    height: 2.75rem;
    border-radius: 9999px;
    background-color: #941b2b;
    border-color: #941b2b;
    color: #ffffff;
    font-weight: 600;
    transition: background-color 180ms ease;
}

.auth-form [type='submit']:hover {
    background-color: #721421;
}

.auth-form [type='submit']:focus-visible {
    outline: 3px solid #e8c7cc;
    outline-offset: 2px;
}

.auth-form a {
    color: #941b2b;
}

.auth-form button[role='checkbox'][data-state='checked'] {
    background-color: #941b2b;
    border-color: #941b2b;
}
</style>
