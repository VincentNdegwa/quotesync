import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { Auth } from '@/types';
import { checkPermissions } from '@/lib/permissions';

export function usePermissions() {
    const page = usePage();

    const permissions = computed<string[]>(
        () => ((page.props.auth as Auth | undefined)?.permissions as string[]) ?? [],
    );

    const permissionSet = computed(() => new Set(permissions.value));

    function can(required: string): boolean {
        return checkPermissions(permissionSet.value, required, 'all');
    }

    function canAny(required: string[]): boolean {
        return checkPermissions(permissionSet.value, required, 'any');
    }

    function canAll(required: string[]): boolean {
        return checkPermissions(permissionSet.value, required, 'all');
    }

    return {
        permissions,
        can,
        canAny,
        canAll,
    };
}
