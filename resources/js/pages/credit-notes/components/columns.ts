import { usePage } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { ArrowUpDown } from 'lucide-vue-next';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useEnums } from '@/composables/useEnums';
import { useFormat } from '@/composables/useFormat';
import type { CreditNoteListRecord } from '@/types';
import CreditNoteTableRowActions from './CreditNoteTableRowActions.vue';

type CreditNoteColumnOptions = {
    onDelete: (creditNoteId: number) => void;
};

const sortableHeader = (
    label: string,
    column: { getIsSorted: () => false | 'asc' | 'desc'; toggleSorting: (desc?: boolean) => void },
    align: 'left' | 'right' = 'left',
): ReturnType<typeof h> => h(
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


export const getCreditNoteColumns = (options: CreditNoteColumnOptions): ColumnDef<CreditNoteListRecord>[] => {
    const { getCreditNoteStatus } = useEnums();
    const page = usePage();
    const defaultCurrency = (page.props.workspace_currency as string) || undefined;

    const formatCurrency = (val: number | string, currency?: string | null): string => {
        return useFormat(currency || defaultCurrency).formatCurrency(val, currency || defaultCurrency);
    };

    const formatDate = (val: string | null): string => {
        return useFormat().formatDate(val);
    };

    const columns: ColumnDef<CreditNoteListRecord>[] = [
        {
            accessorKey: 'credit_note_number',
            header: ({ column }) => sortableHeader('Credit Note #', column),
            cell: ({ row }) => row.original.credit_note_number || '—',
        },
        {
            accessorKey: 'title',
            header: ({ column }) => sortableHeader('Title', column),
            cell: ({ row }) => h('span', { class: 'font-medium' }, row.original.title),
        },
        {
            accessorKey: 'client',
            header: 'Client',
            cell: ({ row }) => row.original.client.company_name || '—',
        },
        {
            accessorKey: 'invoice',
            header: 'Invoice',
            cell: ({ row }) => row.original.invoice?.invoice_number || '—',
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }): ReturnType<typeof h> => {
                const status = getCreditNoteStatus(row.original.status);

                return h(Badge, {
                    variant: status?.badgeColor ?? 'outline',
                    class: ['px-3 py-1 text-xs font-semibold', status?.cssColor],
                }, () => status?.label ?? row.original.status);
            },
        },
        {
            accessorKey: 'total',
            header: ({ column }) => sortableHeader('Total', column, 'right'),
            cell: ({ row }) => h('span', { class: 'text-right' }, formatCurrency(Number(row.original.base_total), row.original.base_currency)),
        },
        {
            accessorKey: 'issue_date',
            header: ({ column }) => sortableHeader('Issue Date', column),
            cell: ({ row }) => formatDate(row.original.issue_date),
        },
        {
            id: 'actions',
            header: '',
            cell: ({ row }) => h(CreditNoteTableRowActions, {
                creditNoteId: row.original.id,
                creditNote: row.original,
                onDelete: () => options.onDelete(row.original.id),
            }),
        },
    ];

    return columns;
};
