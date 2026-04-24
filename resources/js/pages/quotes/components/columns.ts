import type { ColumnDef } from '@tanstack/vue-table';
import { ArrowUpDown } from 'lucide-vue-next';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { QuoteListRecord, QuoteStatusEnum } from '@/types';
import QuoteTableRowActions from './QuoteTableRowActions.vue';

type QuoteColumnOptions = {
    onSend: (quoteId: number) => void;
    onDelete: (quoteId: number) => void;
    quoteStatuses: QuoteStatusEnum[];
};

const sortableHeader = (
    label: string,
    column: { getIsSorted: () => false | 'asc' | 'desc'; toggleSorting: (desc?: boolean) => void },
    align: 'left' | 'right' = 'left',
) => h(
    Button,
    {
        variant: 'ghost',
        class: align === 'right'
            ? 'h-8 w-full justify-center px-0 text-right'
            : 'h-8 justify-center px-0 text-left',
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
    },
    () => [
        label,
        h(ArrowUpDown, { class: 'ml-2 h-4 w-4' }),
    ],
);

export const getQuoteColumns = (options: QuoteColumnOptions): ColumnDef<QuoteListRecord>[] => [
    {
        accessorKey: 'number',
        header: ({ column }) => sortableHeader('Number', column),
        cell: ({ row }) => row.original.number || '—',
    },
    {
        accessorKey: 'title',
        header: ({ column }) => sortableHeader('Title', column),
        cell: ({ row }) => h('span', { class: 'font-medium' }, row.original.title),
    },
    {
        accessorKey: 'client',
        header: 'Client',
        cell: ({ row }) => row.original.client?.company_name || '—',
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const statusData = options.quoteStatuses.find((s) => s.value === row.original.status);

            return h(Badge, {
                variant: statusData?.badgeColor ?? 'outline',
            }, () => statusData?.label ?? row.original.status);
        },
    },
    {
        accessorKey: 'total',
        header: ({ column }) => h('div', { class: 'text-right' }, sortableHeader('Total', column, 'right')),
        cell: ({ row }) => h('div', { class: 'text-right tabular-nums' }, row.original.total.toFixed(2)),
    },
    {
        accessorKey: 'valid_until',
        header: 'Valid until',
        cell: ({ row }) => row.original.valid_until || '—',
    },
    {
        id: 'actions',
        enableSorting: false,
        header: () => h('div', { class: 'w-full text-right' }, 'Actions'),
        cell: ({ row }) => h('div', { class: 'text-right' }, h(QuoteTableRowActions, {
            quote: row.original,
            quoteStatuses: options.quoteStatuses,
            onSend: (quoteId) => options.onSend(quoteId),
            onDelete: (quoteId) => options.onDelete(quoteId),
        })),
    },
];
