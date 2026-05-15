import { router } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { ArrowUpDown } from 'lucide-vue-next';
import { h, type VNode } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import type { PendingInvitation } from '@/types';
import InvitationRowActions from './InvitationRowActions.vue';

type InvitationColumnOptions = {
    onSuccess?: () => void;
};

const sortableHeader = (
    label: string,
    column: {
        getIsSorted: () => false | 'asc' | 'desc';
        toggleSorting: (desc?: boolean) => void;
    },
    align: 'left' | 'right' = 'left',
): VNode =>
    h(
        Button,
        {
            variant: 'ghost',
            class:
                align === 'right'
                    ? 'h-8 w-full px-0'
                    : 'h-8 justify-start px-0 text-left',
            onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
        },
        () => [label, h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
    );

export const getInvitationColumns = (
    options: InvitationColumnOptions,
): ColumnDef<PendingInvitation>[] => [
    {
        accessorKey: 'email',
        header: ({ column }) => sortableHeader('Email', column),
        cell: ({ row }) =>
            h('span', { class: 'font-medium' }, row.original.email),
    },
    {
        id: 'role',
        header: 'Role',
        accessorFn: (row) => row.role_name ?? 'No role',
        cell: ({ row }) =>
            h(
                Badge,
                { variant: 'outline' },
                () => row.original.role_name ?? 'No role',
            ),
    },
    {
        id: 'invited_by',
        header: 'Invited by',
        accessorFn: (row) => row.invited_by ?? 'Unknown',
        cell: ({ row }) =>
            h(
                'span',
                { class: 'text-sm text-muted-foreground' },
                row.original.invited_by ?? 'Unknown',
            ),
    },
    {
        id: 'actions',
        enableSorting: false,
        header: () => h('div', { class: 'w-full text-right' }, 'Actions'),
        cell: ({ row }) =>
            h(
                'div',
                { class: 'text-right' },
                h(InvitationRowActions, {
                    invitation: row.original,
                    onSuccess: options.onSuccess,
                }),
            ),
    },
];
