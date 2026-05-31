export type PermissionRequirement = string | string[];

export type PermissionCheckMode = 'any' | 'all';

export function checkPermissions(
    permissions: Iterable<string>,
    required: PermissionRequirement,
    mode: PermissionCheckMode = 'any',
): boolean {
    const set = permissions instanceof Set ? permissions : new Set(permissions);
    const requiredList = Array.isArray(required) ? required : [required];

    if (requiredList.length === 0) {
        return true;
    }

    if (mode === 'all') {
        return requiredList.every((permission) => set.has(permission));
    }

    return requiredList.some((permission) => set.has(permission));
}
