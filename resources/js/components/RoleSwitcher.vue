<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Check, ChevronsUpDown, ShieldCheck } from 'lucide-vue-next';
import { computed } from 'vue';

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { label } from '@/lib/admin';
import admin from '@/routes/admin';
import type { RoleSummary } from '@/types';

/**
 * Selector de rol: el menú lateral se arma con los permisos del rol elegido.
 * Solo aparece cuando el usuario tiene más de un rol.
 */
const page = usePage();
const { isMobile, state } = useSidebar();

const roles = computed(() => (page.props.auth.roles ?? []) as RoleSummary[]);
const activeRole = computed(() => page.props.auth.activeRole as RoleSummary | null);

const switchRole = (role: RoleSummary): void => {
    if (role.id === activeRole.value?.id) {
        return;
    }

    router.put(admin.activeRole.update().url, { role_id: role.id });
};
</script>

<template>
    <SidebarMenu v-if="roles.length > 1">
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        size="lg"
                        class="border border-[#E8C7CC] bg-[#F9ECEE]/60 data-[state=open]:bg-[#F9ECEE]"
                        tooltip="Cambiar rol"
                    >
                        <div
                            class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-[#941B2B] text-white"
                        >
                            <ShieldCheck class="size-4" />
                        </div>

                        <div class="grid flex-1 text-left leading-tight">
                            <span class="truncate text-[11px] font-medium uppercase tracking-wide text-[#858B93]">
                                Navegando como
                            </span>

                            <span class="truncate text-sm font-semibold text-[#941B2B]">
                                {{ label(activeRole?.name) }}
                            </span>
                        </div>

                        <ChevronsUpDown class="ml-auto size-4 text-[#941B2B]" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>

                <DropdownMenuContent
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg font-['DM_Sans',sans-serif]"
                    :side="isMobile ? 'bottom' : state === 'collapsed' ? 'right' : 'bottom'"
                    align="start"
                    :side-offset="4"
                >
                    <DropdownMenuLabel class="text-xs text-[#737982]">
                        Cambiar de rol
                    </DropdownMenuLabel>

                    <DropdownMenuSeparator />

                    <DropdownMenuItem
                        v-for="role in roles"
                        :key="role.id"
                        class="cursor-pointer gap-2"
                        @click="switchRole(role)"
                    >
                        <ShieldCheck class="size-4 text-[#941B2B]" />

                        <span class="flex-1">{{ label(role.name) }}</span>

                        <Check
                            v-if="role.id === activeRole?.id"
                            class="size-4 text-[#941B2B]"
                        />
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
