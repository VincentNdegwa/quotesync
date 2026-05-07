import { usePage } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { ArrowUpDown } from 'lucide-vue-next';
import { h } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { useFormat } from '@/composables/useFormat';
import type { ClientRecord } from '@/types';
import ClientTableRowActions from './ClientTableRowActions.vue';

type ClientColumnOptions = {
    onEdit: (client: ClientRecord) => void;
};

const sortableHeader = (
    label: string,
    column: {
        getIsSorted: () => false | 'asc' | 'desc';
        toggleSorting: (desc?: boolean) => void;
    },
    align: 'left' | 'right' = 'left',
) =>
    h(
        Button,
        {
            variant: 'ghost',
            class:
                align === 'right'
                    ? 'h-8 w-full justify-center px-0 text-right'
                    : 'h-8 justify-center px-0 text-left',
            onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
        },
        () => [label, h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
    );

export const getClientColumns = (
    options: ClientColumnOptions,
): ColumnDef<ClientRecord>[] => [
    {
        id: 'select',
        enableSorting: false,
        enableHiding: false,
        header: ({ table }) =>
            h(Checkbox, {
                modelValue:
                    table.getIsAllPageRowsSelected() ||
                    (table.getIsSomePageRowsSelected()
                        ? 'indeterminate'
                        : false),
                'onUpdate:modelValue': (value: boolean | 'indeterminate') =>
                    table.toggleAllPageRowsSelected(!!value),
                ariaLabel: 'Select all',
            }),
        cell: ({ row }) =>
            h(Checkbox, {
                modelValue: row.getIsSelected(),
                'onUpdate:modelValue': (value: boolean | 'indeterminate') =>
                    row.toggleSelected(!!value),
                ariaLabel: 'Select row',
            }),
    },
    {
        accessorKey: 'company_name',
        header: ({ column }) => sortableHeader('Company', column),
        cell: ({ row }) =>
            h('span', { class: 'font-medium' }, row.original.company_name),
    },
    {
        accessorKey: 'contact_name',
        header: 'Contact',
        cell: ({ row }) => row.original.contact_name || '—',
    },
    {
        accessorKey: 'email',
        header: 'Email',
        cell: ({ row }) => row.original.email || '—',
    },
    {
        accessorKey: 'country',
        header: 'Country',
        cell: ({ row }) => row.original.country || '—',
    },
    {
        accessorKey: 'quotes_sent_count',
        header: ({ column }) =>
            h(
                'div',
                { class: 'text-center' },
                sortableHeader('Quotes sent', column, 'right'),
            ),
        cell: ({ row }) =>
            h(
                'div',
                { class: 'text-center tabular-nums' },
                row.original.quotes_sent_count ?? 0,
            ),
    },
    {
        accessorKey: 'total_value_won',
        header: ({ column }) =>
            h(
                'div',
                { class: 'text-center' },
                sortableHeader('Value won', column, 'right'),
            ),
        cell: ({ row }) =>
            h(
                'div',
                { class: 'text-center tabular-nums' },
                useFormat().formatCurrency(
                    row.original.total_value_won ?? 0,
                    (usePage().props.workspace_currency as string) || undefined,
                ),
            ),
    },
    {
        accessorKey: 'created_at',
        header: ({ column }) =>
            h(
                'div',
                { class: 'text-center' },
                sortableHeader('Date added', column, 'right'),
            ),
        cell: ({ row }) =>
            h(
                'div',
                { class: 'text-center' },
                useFormat().formatDate(row.original.created_at),
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
                h(ClientTableRowActions, {
                    client: row.original,
                    onEdit: (client: ClientRecord) => options.onEdit(client),
                }),
            ),
    },
];
