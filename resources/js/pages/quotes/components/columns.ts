import type { ColumnDef } from '@tanstack/vue-table';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';
import { useEnums } from '@/composables/useEnums';
import type { QuoteListRecord, QuoteStatusEnum } from '@/types';
import QuoteTableRowActions from './QuoteTableRowActions.vue';
import { Button } from '@/components/ui/button';
import { ArrowUpDown } from 'lucide-vue-next';

type QuoteColumnOptions = {
    quoteStatuses: QuoteStatusEnum[];
    onSend: (quoteId: number) => void;
    onDelete: (quoteId: number) => void;
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


export const getQuoteColumns = (options: QuoteColumnOptions): ColumnDef<QuoteListRecord>[] => {
    const { getQuoteStatus } = useEnums();

    return [
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
                const status = getQuoteStatus(row.original.status);

                return h(Badge, {
                    variant: status?.badgeColor ?? 'outline',
                    class: ['px-3 py-1 text-xs font-semibold', status?.cssColor],
                }, () => status?.label ?? row.original.status);
            },
        },
        {
            accessorKey: 'total',
            header: ({ column }) => h('div', { class: 'text-center' }, sortableHeader('Total', column, 'right')),
            cell: ({ row }) => h('div', { class: 'text-center tabular-nums' }, row.original.total.toFixed(2)),
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
};
