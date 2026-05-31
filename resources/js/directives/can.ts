import type { Directive, DirectiveBinding } from 'vue';
import { checkPermissions, type PermissionRequirement } from '@/lib/permissions';

type CanBindingValue = PermissionRequirement;

function getPermissions(binding: DirectiveBinding): string[] {
    const instance = binding.instance as any;

    return (instance?.$page?.props?.auth?.permissions as string[]) ?? [];
}

function apply(el: HTMLElement, binding: DirectiveBinding<CanBindingValue>) {
    const required = binding.value;
    const mode = binding.modifiers.all ? 'all' : 'any';
    const allowed = checkPermissions(getPermissions(binding), required, mode);

    if (binding.modifiers.disable) {
        (el as any).disabled = !allowed;
        el.style.pointerEvents = allowed ? '' : 'none';
        el.style.opacity = allowed ? '' : '0.5';

        return;
    }

    if (!el.dataset.vCanDisplay) {
        el.dataset.vCanDisplay = el.style.display ?? '';
    }

    el.style.display = allowed ? el.dataset.vCanDisplay : 'none';
}

export const vCan: Directive<HTMLElement, CanBindingValue> = {
    mounted(el, binding) {
        apply(el, binding);
    },
    updated(el, binding) {
        apply(el, binding);
    },
};
