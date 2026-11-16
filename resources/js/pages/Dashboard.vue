
<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import {
    ShieldCheck,
    LogOut,
    MousePointerClick,
    GraduationCap,
} from 'lucide-vue-next'
</script>

<template>
    <Head title="Inicio | Facultad de Odontología - UJED" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto bg-[#F7F8FA] p-4 sm:p-6 lg:px-8"
    >
        <!-- Bienvenida principal -->
        <section
            class="relative overflow-hidden rounded-2xl border border-[#E3E5E8] bg-white p-6 sm:p-10"
        >
            <!-- Elementos decorativos -->
            <div
                class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-[#F9ECEE]"
            />

            <div
                class="pointer-events-none absolute -bottom-24 right-36 h-48 w-48 rounded-full bg-[#F7F8FA]"
            />

            <div class="relative z-10 max-w-3xl">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]"
                    >
                        <GraduationCap class="h-6 w-6" />
                    </div>

                    <div>
                        <p
                            class="text-xs font-bold uppercase tracking-[0.16em] text-[#941B2B]"
                        >
                            Universidad Juárez del Estado de Durango
                        </p>

                        <p class="mt-0.5 text-xs font-medium text-[#737982]">
                            Facultad de Odontología
                        </p>
                    </div>
                </div>

                <h1
                    class="mt-7 text-3xl font-bold tracking-tight text-[#292D32] sm:text-4xl"
                >
                    Bienvenido al sistema de gestión interna
                </h1>

                <p class="mt-3 max-w-2xl text-sm leading-7 text-[#737982] sm:text-base">
                    Administra y consulta las actividades académicas,
                    administrativas y clínicas de la Facultad desde una sola
                    plataforma.
                </p>

                <div
                    class="mt-6 h-1 w-16 rounded-full bg-[#941B2B]"
                />
            </div>
        </section>

        <!-- Guía de uso -->
        <section class="space-y-4">
            <div>
                <h2 class="text-lg font-bold text-[#292D32]">
                    Guía de uso
                </h2>

                <p class="mt-1 text-sm leading-6 text-[#737982]">
                    Consulta estas indicaciones para utilizar correctamente
                    el sistema.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <!-- Navegación -->
                <article
                    class="rounded-2xl border border-[#E3E5E8] bg-white p-5 transition-shadow duration-200 hover:shadow-md sm:p-6"
                >
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]"
                    >
                        <MousePointerClick class="h-5 w-5" />
                    </div>

                    <h3 class="mt-4 text-base font-bold text-[#292D32]">
                        Navegación del sistema
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-[#737982]">
                        Utiliza el menú lateral para acceder a los diferentes
                        módulos del sistema. Selecciona la sección que
                        necesitas y consulta las opciones disponibles de
                        acuerdo con los permisos asignados a tu cuenta.
                    </p>
                </article>

                <!-- Cerrar sesión -->
                <article
                    class="rounded-2xl border border-[#E3E5E8] bg-white p-5 transition-shadow duration-200 hover:shadow-md sm:p-6"
                >
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]"
                    >
                        <LogOut class="h-5 w-5" />
                    </div>

                    <h3 class="mt-4 text-base font-bold text-[#292D32]">
                        Cómo cerrar sesión
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-[#737982]">
                        Cuando termines tus actividades, sigue estos pasos
                        para salir de tu cuenta:
                    </p>

                    <ol class="mt-4 space-y-3">
                        <li class="flex items-start gap-3">
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#F9ECEE] text-xs font-bold text-[#941B2B]"
                            >
                                1
                            </span>

                            <p class="text-sm leading-6 text-[#737982]">
                                Dirígete a la parte inferior del menú lateral.
                            </p>
                        </li>

                        <li class="flex items-start gap-3">
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#F9ECEE] text-xs font-bold text-[#941B2B]"
                            >
                                2
                            </span>

                            <p class="text-sm leading-6 text-[#737982]">
                                Localiza tu nombre o abre las opciones de tu
                                cuenta.
                            </p>
                        </li>

                        <li class="flex items-start gap-3">
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#F9ECEE] text-xs font-bold text-[#941B2B]"
                            >
                                3
                            </span>

                            <p class="text-sm leading-6 text-[#737982]">
                                Selecciona
                                <strong class="font-semibold text-[#292D32]">
                                    Cerrar sesión
                                </strong>
                                para finalizar tu acceso al sistema.
                            </p>
                        </li>
                    </ol>
                </article>
            </div>
        </section>

        <!-- Seguridad -->
        <section
            class="flex items-start gap-4 rounded-2xl border border-[#E3E5E8] bg-white p-5 sm:p-6"
        >
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]"
            >
                <ShieldCheck class="h-5 w-5" />
            </div>

            <div>
                <h2 class="text-sm font-bold text-[#292D32]">
                    Seguridad de tu cuenta
                </h2>

                <p class="mt-1 text-sm leading-6 text-[#737982]">
                    Protege tus credenciales y evita compartirlas con otras
                    personas. Si utilizas un equipo compartido, cierra tu
                    sesión antes de retirarte para proteger la información
                    académica, administrativa y clínica de la Facultad de
                    Odontología.
                </p>
            </div>
        </section>
    </div>
</template>
