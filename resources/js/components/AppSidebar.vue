
<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';

import {
    Activity,
    BadgeCheck,
    Banknote,
    BookOpen,
    Boxes,
    BriefcaseBusiness,
    Building2,
    CalendarCheck,
    CalendarClock,
    CalendarDays,
    CalendarRange,
    ClipboardList,
    ClipboardPlus,
    Contact,
    CreditCard,
    DoorOpen,
    FileChartColumn,
    FileClock,
    GraduationCap,
    HandCoins,
    Hospital,
    KeyRound,
    LayoutDashboard,
    LayoutGrid,
    Library,
    ListTree,
    Package,
    Pill,
    Presentation,
    Receipt,
    ScrollText,
    Settings,
    Shield,
    ShoppingCart,
    Stethoscope,
    Store,
    Tag,
    Ticket,
    TriangleAlert,
    Trophy,
    Truck,
    UserRoundCheck,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';

import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import RoleSwitcher from '@/components/RoleSwitcher.vue';

import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';

import admin from '@/routes/admin';
import type { NavItem } from '@/types';

interface MenuLink {
    id: number;
    menu_id: number;
    name: string;
    icon: string | null;
    order: number;
    route: string | null;
    url: string;
    permission_id: number | null;
}

interface Menu {
    id: number;
    icon: string | null;
    name: string;
    order: number;
    route: string | null;
    url: string | null;
    is_submenu: boolean;
    links: MenuLink[];
}

const page = usePage();

// Reactivo: el menú cambia cuando el usuario cambia de rol.
const menus = computed(() => (page.props.menus ?? []) as Menu[]);

const icons = {
    Activity,
    BadgeCheck,
    Banknote,
    BookOpen,
    Boxes,
    BriefcaseBusiness,
    Building2,
    CalendarCheck,
    CalendarClock,
    CalendarDays,
    CalendarRange,
    ClipboardList,
    ClipboardPlus,
    Contact,
    CreditCard,
    DoorOpen,
    FileChartColumn,
    FileClock,
    GraduationCap,
    HandCoins,
    Hospital,
    KeyRound,
    LayoutDashboard,
    LayoutGrid,
    Library,
    ListTree,
    Package,
    Pill,
    Presentation,
    Receipt,
    ScrollText,
    Settings,
    Shield,
    ShoppingCart,
    Stethoscope,
    Store,
    Tag,
    Ticket,
    TriangleAlert,
    Trophy,
    Truck,
    UserRoundCheck,
    Users,
    Wallet,
};

function getIcon(icon: string | null) {
    if (!icon) {
        return LayoutGrid;
    }

    return icons[icon as keyof typeof icons] ?? LayoutGrid;
}

const mainNavItems = computed<NavItem[]>(() => menus.value.map((menu) => {
    if (menu.is_submenu) {
        return {
            title: menu.name,
            icon: getIcon(menu.icon),
            children: (menu.links ?? []).map((link) => ({
                title: link.name,
                href: link.url,
                icon: link.icon ? getIcon(link.icon) : undefined,
            })),
        };
    }

    return {
        title: menu.name,
        href: menu.url ?? '#',
        icon: getIcon(menu.icon),
    };
}));

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                    >
                        <Link :href="admin.dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>

            <RoleSwitcher />
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <slot />
</template>
