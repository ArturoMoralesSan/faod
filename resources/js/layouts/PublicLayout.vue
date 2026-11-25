<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, ExternalLink, Mail, MapPin, Menu, Phone, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import { IMAGES, INSTITUTION } from '@/lib/institution';
import { home, login } from '@/routes';
import admin from '@/routes/admin';
import site from '@/routes/public';

/** Encabezado y pie de página del sitio público. */
const page = usePage<{ auth: { user: { name: string } | null } }>();
const user = computed(() => page.props.auth?.user ?? null);
const entry = computed(() => (user.value ? { href: admin.dashboard().url, label: 'Ir al panel' } : { href: login().url, label: 'Iniciar sesión' }));

const NAV = [
    { label: 'Inicio', href: home().url, match: (url: string) => url === '/' },
    { label: 'Oferta académica', href: site.programs().url, match: (url: string) => url.startsWith('/oferta-academica') },
    { label: 'Cursos y actividades', href: site.activities().url, match: (url: string) => url.startsWith('/cursos-y-actividades') },
    { label: 'Atención clínica', href: site.clinical().url, match: (url: string) => url.startsWith('/atencion-clinica') },
];

const current = computed(() => page.url.split('?')[0]);
const menuOpen = ref(false);
watch(current, () => (menuOpen.value = false));

const year = new Date().getFullYear();
</script>

<template>
    <div class="flex min-h-svh flex-col bg-[#F7F8FA] font-['DM_Sans',sans-serif] text-[#292D32]">
        <a href="#contenido" class="sr-only z-50 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-[#941B2B] focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Saltar al contenido</a>

        <!-- Barra institucional -->
        <div class="bg-[#3D0912] text-[11px] text-white/80">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2 px-4 py-2 sm:px-6">
                <span class="font-semibold uppercase tracking-[0.16em]">{{ INSTITUTION.university }}</span>
                <span class="hidden items-center gap-4 sm:flex">
                    <a :href="INSTITUTION.phoneHref" class="inline-flex items-center gap-1.5 hover:text-white"><Phone class="size-3" /> {{ INSTITUTION.phone }}</a>
                    <a :href="`mailto:${INSTITUTION.email}`" class="inline-flex items-center gap-1.5 hover:text-white"><Mail class="size-3" /> {{ INSTITUTION.email }}</a>
                </span>
            </div>
        </div>

        <!-- Encabezado -->
        <header class="sticky top-0 z-30 border-b border-[#E3E5E8] bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
                <Link :href="home().url" class="flex items-center gap-3">
                    <span class="flex size-11 items-center justify-center rounded-xl bg-[#941B2B] p-1.5">
                        <img :src="IMAGES.logoUjedWhite" :alt="INSTITUTION.university" class="h-full w-auto" />
                    </span>
                    <span>
                        <span class="block font-['Manrope',sans-serif] text-[15px] font-extrabold leading-tight text-[#1F2226]">{{ INSTITUTION.name }}</span>
                        <span class="block text-[11px] font-semibold uppercase tracking-[0.14em] text-[#941B2B]">{{ INSTITUTION.short }} · {{ INSTITUTION.universityShort }}</span>
                    </span>
                </Link>

                <nav class="hidden items-center gap-1 lg:flex" aria-label="Principal">
                    <Link
                        v-for="item in NAV"
                        :key="item.href"
                        :href="item.href"
                        :class="['rounded-full px-3.5 py-2 text-sm font-semibold transition-colors', item.match(current) ? 'bg-[#F9ECEE] text-[#941B2B]' : 'text-[#525960] hover:text-[#292D32]']"
                        :aria-current="item.match(current) ? 'page' : undefined"
                    >
                        {{ item.label }}
                    </Link>
                </nav>

                <div class="flex items-center gap-2">
                    <Link
                        :href="entry.href"
                        class="hidden h-10 items-center gap-2 rounded-full bg-[#941B2B] px-5 text-sm font-semibold text-white transition-colors hover:bg-white hover:text-[#292D32] hover:ring-1 hover:ring-inset hover:ring-[#941B2B] focus-visible:outline focus-visible:outline-[3px] focus-visible:outline-offset-2 focus-visible:outline-[#E8C7CC] sm:inline-flex"
                    >
                        {{ entry.label }} <ArrowRight class="size-4" />
                    </Link>
                    <button
                        type="button"
                        class="inline-flex size-10 items-center justify-center rounded-xl border border-[#E3E5E8] text-[#383D43] lg:hidden"
                        :aria-expanded="menuOpen"
                        aria-controls="menu-movil"
                        aria-label="Menú"
                        @click="menuOpen = !menuOpen"
                    >
                        <X v-if="menuOpen" class="size-5" />
                        <Menu v-else class="size-5" />
                    </button>
                </div>
            </div>

            <nav v-if="menuOpen" id="menu-movil" class="border-t border-[#E3E5E8] bg-white px-4 py-3 lg:hidden" aria-label="Principal">
                <Link
                    v-for="item in NAV"
                    :key="item.href"
                    :href="item.href"
                    :class="['block rounded-xl px-3 py-2.5 text-sm font-semibold', item.match(current) ? 'bg-[#F9ECEE] text-[#941B2B]' : 'text-[#383D43]']"
                >
                    {{ item.label }}
                </Link>
                <Link :href="entry.href" class="mt-2 flex h-11 items-center justify-center gap-2 rounded-full bg-[#941B2B] text-sm font-semibold text-white">
                    {{ entry.label }} <ArrowRight class="size-4" />
                </Link>
            </nav>
        </header>

        <main id="contenido" class="flex-1">
            <slot />
        </main>

        <!-- Pie -->
        <footer class="bg-[#941B2B] text-white/85">
            <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-[1.4fr_1fr_1fr]">
                <div>
                    <div class="flex items-center gap-4">
                        <img :src="IMAGES.logoUjedWhite" :alt="INSTITUTION.university" class="h-16 w-auto" />
                        <div>
                            <p class="font-['Manrope',sans-serif] text-lg font-bold text-white">{{ INSTITUTION.name }}</p>
                            <p class="text-xs">{{ INSTITUTION.university }}</p>
                        </div>
                    </div>
                    <p class="mt-5 max-w-sm text-sm leading-6">{{ INSTITUTION.purpose }}</p>
                </div>

                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-white">Explora</p>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li v-for="item in NAV" :key="item.href">
                            <Link :href="item.href" class="underline-offset-4 hover:text-white hover:underline">{{ item.label }}</Link>
                        </li>
                        <li><Link :href="entry.href" class="underline-offset-4 hover:text-white hover:underline">{{ entry.label }}</Link></li>
                    </ul>
                </div>

                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-white">Contacto</p>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li>
                            <a :href="INSTITUTION.map" target="_blank" rel="noopener" class="flex gap-2 underline-offset-4 hover:text-white hover:underline"><MapPin class="mt-0.5 size-4 shrink-0" /> {{ INSTITUTION.address }}</a>
                        </li>
                        <li><a :href="INSTITUTION.phoneHref" class="flex items-center gap-2 underline-offset-4 hover:text-white hover:underline"><Phone class="size-4" /> {{ INSTITUTION.phone }}</a></li>
                        <li><a :href="`mailto:${INSTITUTION.email}`" class="flex items-center gap-2 underline-offset-4 hover:text-white hover:underline"><Mail class="size-4" /> {{ INSTITUTION.email }}</a></li>
                        <li>
                            <a :href="INSTITUTION.website" target="_blank" rel="noopener" class="flex items-center gap-2 underline-offset-4 hover:text-white hover:underline"><ExternalLink class="size-4" /> Página en ujed.mx</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-white/20">
                <p class="mx-auto max-w-6xl px-4 py-5 text-xs sm:px-6">© {{ year }} {{ INSTITUTION.name }} · {{ INSTITUTION.universityShort }}. {{ INSTITUTION.system }}.</p>
            </div>
        </footer>
    </div>
</template>
