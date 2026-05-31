import { describe, expect, it } from 'vitest';
import { checkPermissions } from '@/lib/permissions';

describe('permissions', () => {
    it('checks a single permission', () => {
        expect(checkPermissions(['a'], 'a', 'all')).toBe(true);
        expect(checkPermissions(['a'], 'b', 'all')).toBe(false);
    });

    it('checks any permissions', () => {
        expect(checkPermissions(['a'], ['a', 'b'], 'any')).toBe(true);
        expect(checkPermissions(['a'], ['b', 'c'], 'any')).toBe(false);
    });

    it('checks all permissions', () => {
        expect(checkPermissions(['a', 'b'], ['a', 'b'], 'all')).toBe(true);
        expect(checkPermissions(['a'], ['a', 'b'], 'all')).toBe(false);
    });
});
