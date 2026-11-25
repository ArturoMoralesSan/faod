import { usePage } from '@inertiajs/vue3';

/**
 * ¿El usuario tiene el permiso? Solo sirve para ocultar botones: el servidor
 * valida de nuevo cada acción. "*" = SuperAdmin.
 */
export function can(permission: string): boolean {
    const permissions = usePage().props.auth?.permissions ?? [];

    return permissions.includes('*') || permissions.includes(permission);
}
