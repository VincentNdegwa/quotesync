import { usePage } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { ArrowUpDown } from 'lucide-vue-next';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { useFormat } from '@/composables/useFormat';
import type { CatalogItemRecord } from '@/types';
import DataTableRowActions from './DataTableRowActions.vue';

type CatalogColumnOptions = {
    marginPercent: (item: CatalogItemRecord) => number;
    onEdit: (item: CatalogItemRecord) => void;
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
                    ? 'h-8 w-full px-0'
                    : 'h-8 justify-start px-0 text-left',
            onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
        },
        () => [label, h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
    );

export const getCatalogColumns = (
    options: CatalogColumnOptions,
): ColumnDef<CatalogItemRecord>[] => [
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
        accessorKey: 'name',
        header: ({ column }) => sortableHeader('Name', column),
        cell: ({ row }) =>
            h('span', { class: 'font-medium' }, row.original.name),
    },
    {
        accessorKey: 'sku',
        header: 'SKU',
        cell: ({ row }) => row.original.sku || '—',
    },
    {
        id: 'category',
        header: 'Category',
        accessorFn: (row) => row.category?.name ?? 'Uncategorized',
    },
    {
        accessorKey: 'unit_price',
        header: ({ column }) =>
            h(
                'div',
                { class: 'text-center' },
                sortableHeader('Unit price', column, 'right'),
            ),
        cell: ({ row }) =>
            h(
                'div',
                { class: 'text-center tabular-nums' },
                useFormat().formatCurrency(
                    row.original.unit_price,
                    (usePage().props.workspace_currency as string) || undefined,
                ),
            ),
    },
    {
        id: 'margin_percent',
        header: ({ column }) =>
            h(
                'div',
                { class: 'text-center' },
                sortableHeader('Margin %', column, 'right'),
            ),
        accessorFn: (row) => options.marginPercent(row),
        cell: ({ row }) =>
            h(
                'div',
                { class: 'text-center tabular-nums' },
                `${options.marginPercent(row.original)}%`,
            ),
    },
    {
        accessorKey: 'usage_count',
        header: ({ column }) =>
            h(
                'div',
                { class: 'text-center' },
                sortableHeader('Usage', column, 'right'),
            ),
        cell: ({ row }) =>
            h(
                'div',
                { class: 'text-center tabular-nums' },
                row.original.usage_count,
            ),
    },
    {
        accessorKey: 'is_active',
        header: 'Status',
        cell: ({ row }) =>
            h(
                Badge,
                {
                    variant: row.original.is_active ? 'default' : 'secondary',
                },
                () => (row.original.is_active ? 'Active' : 'Inactive'),
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
                h(DataTableRowActions, {
                    item: row.original,
                    onEdit: (item: CatalogItemRecord) => options.onEdit(item),
                }),
            ),
    },
];
