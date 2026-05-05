import { usePage } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { ArrowUpDown } from 'lucide-vue-next';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useEnums } from '@/composables/useEnums';
import { useFormat } from '@/composables/useFormat';
import type { InvoiceListRecord, InvoiceStatusEnum } from '@/types';
import InvoiceTableRowActions from './InvoiceTableRowActions.vue';

type InvoiceColumnOptions = {
    invoiceStatuses: InvoiceStatusEnum[];
    onDelete: (invoiceId: number) => void;
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


export const getInvoiceColumns = (options: InvoiceColumnOptions): ColumnDef<InvoiceListRecord>[] => {
    const { getInvoiceStatus } = useEnums();
    const page = usePage();
    const defaultCurrency = (page.props.workspace_currency as string) || undefined;

    const formatCurrency = (val: number | string, currency?: string | null) => {
        return useFormat(currency || defaultCurrency).formatCurrency(val, currency || defaultCurrency);
    };

    const formatDate = (val: string | null) => {
        return useFormat().formatDate(val);
    };

    const columns: ColumnDef<InvoiceListRecord>[] = [
        {
            accessorKey: 'invoice_number',
            header: ({ column }) => sortableHeader('Invoice #', column),
            cell: ({ row }) => row.original.invoice_number || '—',
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
                const status = getInvoiceStatus(row.original.status);

                return h(Badge, {
                    variant: status?.badgeColor ?? 'outline',
                    class: ['px-3 py-1 text-xs font-semibold', status?.cssColor],
                }, () => status?.label ?? row.original.status);
            },
        },
        {
            accessorKey: 'total',
            header: ({ column }) => sortableHeader('Total', column, 'right'),
            cell: ({ row }) => h('span', { class: 'text-right' }, formatCurrency(row.original.total, row.original.base_currency)),
        },
        {
            accessorKey: 'due_date',
            header: ({ column }) => sortableHeader('Due Date', column),
            cell: ({ row }) => formatDate(row.original.due_date),
        },
        {
            id: 'actions',
            header: '',
            cell: ({ row }) => h(InvoiceTableRowActions, {
                invoiceId: row.original.id,
                invoice: row.original,
                invoiceStatuses: options.invoiceStatuses,
                onDelete: () => options.onDelete(row.original.id),
            }),
        },
    ];

    return columns;
};
