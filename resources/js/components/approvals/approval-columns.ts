import type { ColumnDef } from '@tanstack/vue-table';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

export type Approval = {
    id: number;
    created_at: string;
    quote: {
        id: number;
        number: string | null;
        title: string;
        total: number;
        currency: string;
        client: { id: number; company_name: string } | null;
        created_by_name: string | null;
    };
    approval_rule: {
        id: number;
        trigger_type: string;
        threshold_value: number | null;
    } | null;
};

export const getApprovalColumns = (
    currency: string,
    onApprove: (approval: Approval) => void,
    onReject: (approval: Approval) => void,
): ColumnDef<Approval>[] => [
    {
        accessorKey: 'quote.number',
        header: 'Quote #',
        cell: ({ row }) => row.original.quote.number || '—',
    },
    {
        accessorKey: 'quote.title',
        header: 'Title',
        cell: ({ row }) => row.original.quote.title,
    },
    {
        accessorKey: 'quote.client.company_name',
        header: 'Client',
        cell: ({ row }) => row.original.quote.client?.company_name || '—',
    },
    {
        accessorKey: 'quote.total',
        header: 'Amount',
        cell: ({ row }): string => {
            const value = row.original.quote.total;

            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency,
            }).format(value);
        },
    },
    {
        accessorKey: 'quote.created_by_name',
        header: 'Created By',
        cell: ({ row }) => row.original.quote.created_by_name || '—',
    },
    {
        accessorKey: 'created_at',
        header: 'Submitted',
        cell: ({ row }): string => {
            const diff = Math.floor(
                (Date.now() - new Date(row.original.created_at).getTime()) /
                    86400000,
            );

            if (diff === 0) {
                return 'today';
            }

            if (diff === 1) {
                return 'yesterday';
            }

            return `${diff} days ago`;
        },
    },
    {
        accessorKey: 'approval_rule',
        header: 'Rule',
        cell: ({ row }): any => {
            const rule = row.original.approval_rule;

            if (!rule) {
                return h(Badge, { variant: 'outline' }, 'Manual');
            }

            if (rule.trigger_type === 'value_above') {
                return h(Badge, { variant: 'secondary' }, 'Value above');
            }

            if (rule.trigger_type === 'value_below') {
                return h(Badge, { variant: 'secondary' }, 'Value below');
            }

            if (rule.trigger_type === 'client') {
                return h(Badge, { variant: 'secondary' }, 'Client');
            }

            return h(Badge, { variant: 'outline' }, rule.trigger_type);
        },
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) =>
            h('div', { class: 'flex gap-2' }, [
                h(
                    Button,
                    {
                        size: 'sm',
                        variant: 'default',
                        onClick: () => onApprove(row.original),
                    },
                    'Approve',
                ),
                h(
                    Button,
                    {
                        size: 'sm',
                        variant: 'destructive',
                        onClick: () => onReject(row.original),
                    },
                    'Reject',
                ),
            ]),
    },
];
