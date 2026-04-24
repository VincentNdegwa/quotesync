import type { ColumnDef } from '@tanstack/vue-table';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';
import { useEnums } from '@/composables/useEnums';
import type { QuoteListRecord, QuoteStatusEnum } from '@/types';
import QuoteTableRowActions from './QuoteTableRowActions.vue';

type QuoteColumnOptions = {
    quoteStatuses: QuoteStatusEnum[];
    onSend: (quoteId: number) => void;
    onDelete: (quoteId: number) => void;
};

const sortableHeader = (title: string, column: any, align: 'left' | 'center' | 'right' = 'left') => {
    const isSorted = column.getIsSorted();

    return h('div', { class: `flex items-center gap-2 ${align === 'right' ? 'justify-end' : align === 'center' ? 'justify-center' : ''}` }, [
        title,
        isSorted ? (
            isSorted === 'asc' ? '↑' : '↓'
        ) : (
            h('button', {
                onClick: () => column.toggleSorting(),
                class: 'opacity-50 hover:opacity-100',
            }, '↕')
        ),
    ]);
};

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
