import type { ColumnDef } from '@tanstack/vue-table';
import { h } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { useEnums } from '@/composables/useEnums';
import type { QuoteListRecord, QuoteStatusEnum } from '@/types';
import QuoteTableRowActions from './QuoteTableRowActions.vue';
import { Button } from '@/components/ui/button';
import { ArrowUpDown } from 'lucide-vue-next';
import { useFormat } from '@/composables/useFormat';

type QuoteColumnOptions = {
    quoteStatuses: QuoteStatusEnum[];
    onSend: (quoteId: number) => void;
    onDelete: (quoteId: number) => void;
    onApprove?: (quoteId: number) => void;
    onReject?: (quoteId: number) => void;
    isClient?: boolean;
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

    const columns: ColumnDef<QuoteListRecord>[] = [
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
    ];

    if (!options.isClient) {
        columns.push({
            accessorKey: 'client',
            header: 'Client',
            cell: ({ row }) => row.original.client?.company_name || '—',
        });
    }

    columns.push(
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => {
                // Use client_status if available (for portal users), otherwise use status
                const statusValue = (row.original as any).client_status || row.original.status;
                const status = getQuoteStatus(statusValue);

                return h(Badge, {
                    variant: status?.badgeColor ?? 'outline',
                    class: ['px-3 py-1 text-xs font-semibold', status?.cssColor],
                }, () => status?.label ?? statusValue);
            },
        },
        {
            accessorKey: 'base_total',
            header: ({ column }) => h('div', { class: 'text-center' }, sortableHeader('Total', column, 'right')),
            cell: ({ row }) => {
                const total = typeof row.original.base_total === 'string' ? parseFloat(row.original.base_total) : row.original.base_total;
                return h('div', { class: 'text-center tabular-nums' }, useFormat().formatCurrency(total ?? 0, row.original.base_currency || (usePage().props.workspace_currency as string) || undefined));
            },
        },
        {
            accessorKey: 'valid_until',
            header: 'Valid until',
            cell: ({ row }) => useFormat().formatDate(row.original.valid_until) || '—',
        }
    );

    // Only show win probability for non-client users
    if (!options.isClient) {
        columns.push({
            accessorKey: 'win_probability',
            header: ({ column }) => sortableHeader('Win Probability', column),
            cell: ({ row }) => {
                const probability = row.original.win_probability;
                if (probability === null || probability === undefined) return '—';

                const getColor = (p: number) => {
                    if (p >= 70) return 'text-green-600';
                    if (p >= 40) return 'text-yellow-600';
                    return 'text-red-600';
                };

                return h('div', { class: 'flex items-end gap-2' }, [
                    h('div', { class: 'flex-1 h-2 rounded-full bg-gray-200 overflow-hidden' }, [
                        h('div', { 
                            class: 'h-full rounded-full',
                            style: { width: `${probability}%`, backgroundColor: probability >= 70 ? '#22c55e' : probability >= 40 ? '#eab308' : '#ef4444' }
                        })
                    ]),
                    h('span', { class: `text-xs font-bold tabular-nums ${getColor(probability)}` }, `${Math.round(probability)}%`)
                ]);
            },
        });
    }

    columns.push({
        id: 'actions',
        enableSorting: false,
        header: () => h('div', { class: 'w-full text-right' }, 'Actions'),
        cell: ({ row }) => h('div', { class: 'text-right' }, h(QuoteTableRowActions, {
            quote: row.original,
            quoteStatuses: options.quoteStatuses,
            onSend: (quoteId: number) => options.onSend(quoteId),
            onDelete: (quoteId: number) => options.onDelete(quoteId),
            onApprove: options.onApprove ? (quoteId: number) => options.onApprove!(quoteId) : undefined,
            onReject: options.onReject ? (quoteId: number) => options.onReject!(quoteId) : undefined,
            isClient: options.isClient ?? false,
        })),
    });

    return columns;
};
