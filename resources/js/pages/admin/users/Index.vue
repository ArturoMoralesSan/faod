<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { FilterX, KeyRound, Plus, Power, Users } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref } from 'vue';

import DataTable from '@/components/admin/DataTable.vue';
import FilterSelect from '@/components/admin/FilterSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import TableActions from '@/components/admin/TableActions.vue';
import { formatDateTime } from '@/lib/admin';
import admin from '@/routes/admin';

interface Role {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    last_login_at: string | null;
    roles: Role[];
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface UsersPagination {
    data: User[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
}

const props = defineProps<{
    users: UsersPagination;
    filters: {
        search?: string;
        is_active?: string;
    };
}>();

const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.is_active ?? '');

const statusOptions = [
    { value: '1', label: 'Activas' },
    { value: '0', label: 'Desactivadas' },
];

const hasFilters = () => Boolean(search.value.trim() || status.value);

const submitSearch = () => {
    router.get(
        admin.users.index().url,
        {
            search: search.value.trim() || undefined,
            is_active: status.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const toggleActive = (user: User) => {
    Swal.fire({
        title: user.is_active ? '¿Desactivar cuenta?' : '¿Activar cuenta?',
        text: user.is_active
            ? `"${user.name}" no podrá iniciar sesión y sus sesiones abiertas se cerrarán.`
            : `"${user.name}" podrá volver a iniciar sesión.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: user.is_active ? 'Sí, desactivar' : 'Sí, activar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        confirmButtonColor: '#941B2B',
        cancelButtonColor: '#737982',
    }).then((result) => {
        if (result.isConfirmed) {
            router.patch(admin.users.toggleActive(user.id).url, {}, { preserveScroll: true });
        }
    });
};

const sendReset = (user: User) => {
    Swal.fire({
        title: '¿Enviar enlace para restablecer contraseña?',
        text: `Se enviará un correo a ${user.email} con un enlace para crear una nueva contraseña.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, enviar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        confirmButtonColor: '#941B2B',
        cancelButtonColor: '#737982',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(admin.users.passwordReset(user.id).url, {}, { preserveScroll: true });
        }
    });
};

const clearFilters = () => {
    search.value = '';
    status.value = '';

    router.get(
        admin.users.index().url,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const deleteUser = (user: User) => {
    Swal.fire({
        title: '¿Eliminar usuario?',
        text: `Se eliminará a "${user.name}". Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        confirmButtonColor: '#941B2B',
        cancelButtonColor: '#737982',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(admin.users.destroy(user.id).url, {
                preserveScroll: true,
            });
        }
    });
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Panel',
                href: admin.dashboard(),
            },
            {
                title: 'Usuarios',
                href: admin.users.index,
            },
        ],
    },
});
</script>

<template>
    <Head title="Usuarios" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-hidden bg-[#F7F8FA] p-4 sm:p-6">
        <PageHeader
            title="Usuarios"
            description="Administra los usuarios y sus roles."
            section="Administración"
            :icon="Users"
            :action-icon="Plus"
            action-label="Nuevo usuario"
            :action-href="admin.users.create().url"
        />

        <DataTable
            :pagination="users"
            :is-empty="users.data.length === 0"
            :empty-colspan="5"
            v-model:search="search"
            search-placeholder="Buscar usuario..."
            :has-filters="hasFilters()"
            @search="submitSearch"
            @clear-filters="clearFilters"
        >
            <template #filters>
                <FilterSelect v-model="status" placeholder="Todas las cuentas" :options="statusOptions" @change="submitSearch" />
            </template>

            <template #active-filters>
                <div
                    v-if="search.trim()"
                    class="flex flex-wrap items-center gap-2"
                >
                    <span
                        class="inline-flex items-center gap-2 rounded-full border border-[#E8C7CC] bg-[#F9ECEE] px-3 py-1.5 text-xs font-medium text-[#941B2B]"
                    >
                        <FilterX class="h-3.5 w-3.5" />
                        Búsqueda: {{ search }}
                    </span>
                </div>
            </template>

            <template #table-head>
                <tr>
                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">
                        Usuario
                    </th>

                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">
                        Correo
                    </th>

                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">
                        Roles
                    </th>

                    <th class="px-5 py-4 font-semibold text-[#383D43] sm:px-6">
                        Cuenta
                    </th>

                    <th class="px-5 py-4 text-right font-semibold text-[#383D43] sm:px-6">
                        Acciones
                    </th>
                </tr>
            </template>

            <template #table-body>
                <tr
                    v-for="user in users.data"
                    :key="user.id"
                    class="transition-colors hover:bg-[#F4F5F6]"
                >
                    <td class="px-5 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]"
                            >
                                <Users class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <p class="font-semibold text-[#292D32]">
                                    {{ user.name }}
                                </p>

                                <p class="mt-0.5 text-xs text-[#858B93]">
                                    ID: {{ user.id }}
                                </p>
                            </div>
                        </div>
                    </td>

                    <td class="px-5 py-4 text-[#525960] sm:px-6">
                        <span class="break-all">
                            {{ user.email }}
                        </span>
                    </td>

                    <td class="px-5 py-4 sm:px-6">
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="role in user.roles"
                                :key="role.id"
                                class="inline-flex items-center rounded-full border border-[#E8C7CC] bg-[#F9ECEE] px-2.5 py-1 text-xs font-semibold text-[#941B2B]"
                            >
                                {{ role.name }}
                            </span>

                            <span
                                v-if="!user.roles.length"
                                class="text-xs italic text-[#858B93]"
                            >
                                Sin rol asignado
                            </span>
                        </div>
                    </td>

                    <td class="px-5 py-4 sm:px-6">
                        <StatusBadge :value="user.is_active" />
                        <p class="mt-1 text-xs text-[#858B93]">
                            {{ user.last_login_at ? `Último acceso: ${formatDateTime(user.last_login_at)}` : 'Sin accesos' }}
                        </p>
                    </td>

                    <TableActions
                        :edit-url="admin.users.edit(user.id).url"
                        :show-view="false"
                        item-name="usuario"
                        @delete="deleteUser(user)"
                    >
                        <button
                            type="button"
                            title="Enviar enlace para restablecer contraseña"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-[#737982] transition-colors hover:bg-[#F1F2F4] hover:text-[#292D32]"
                            @click="sendReset(user)"
                        >
                            <KeyRound :size="16" :stroke-width="1.8" />
                        </button>

                        <button
                            type="button"
                            :title="user.is_active ? 'Desactivar cuenta' : 'Activar cuenta'"
                            :class="[
                                'inline-flex h-9 w-9 items-center justify-center rounded-lg transition-colors hover:bg-[#F1F2F4]',
                                user.is_active ? 'text-[#187744]' : 'text-[#941B2B]',
                            ]"
                            @click="toggleActive(user)"
                        >
                            <Power :size="16" :stroke-width="1.8" />
                        </button>
                    </TableActions>
                </tr>
            </template>

            <template #empty>
                <div class="flex flex-col items-center justify-center gap-3 py-8">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#F9ECEE] text-[#941B2B]"
                    >
                        <Users class="h-6 w-6" />
                    </div>

                    <div class="text-center">
                        <p class="font-semibold text-[#292D32]">
                            No se encontraron usuarios
                        </p>

                        <p class="mt-1 text-sm text-[#737982]">
                            {{
                                search.trim()
                                    ? 'Prueba con otro término de búsqueda o limpia los filtros.'
                                    : 'Todavía no hay usuarios registrados.'
                            }}
                        </p>
                    </div>
                </div>
            </template>
        </DataTable>
    </div>
</template>