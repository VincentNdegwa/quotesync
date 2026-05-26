import type { ColumnDef } from '@tanstack/vue-table';
import { Trash2 } from 'lucide-vue-next';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';

export type Rule = {
    id: number;
    trigger_type: string;
    threshold_value: number | null;
    client_id: number | null;
    client: { id: number; company_name: string } | null;
    approver_id: number;
    approver: { id: number; name: string };
    is_active: boolean;
};

export const getRuleColumns = (
    currency: string,
    onToggle: (rule: Rule, active: boolean) => void,
    onDelete: (rule: Rule) => void,
): ColumnDef<Rule>[] => [
    {
        accessorKey: 'trigger_type',
        header: 'Trigger',
        cell: ({ row }): any => {
            const rule = row.original;

            if (rule.trigger_type === 'value_above') {
                return h(
                    Badge,
                    { variant: 'secondary' },
                    () =>
                        `Value above ${new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(rule.threshold_value ?? 0)}`,
                );
            }

            if (rule.trigger_type === 'value_below') {
                return h(
                    Badge,
                    { variant: 'secondary' },
                    () =>
                        `Value below ${new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(rule.threshold_value ?? 0)}`,
                );
            }

            if (rule.trigger_type === 'client') {
                return h(
                    Badge,
                    { variant: 'secondary' },
                    () => `Client: ${rule.client?.company_name ?? '—'}`,
                );
            }

            if (rule.trigger_type === 'all_quotes') {
                return h(Badge, { variant: 'secondary' }, () => 'All quotes');
            }

            return h(Badge, { variant: 'outline' }, () => rule.trigger_type);
        },
    },
    {
        accessorKey: 'approver.name',
        header: 'Approver',
        cell: ({ row }) => row.original.approver.name,
    },
    {
        accessorKey: 'client.company_name',
        header: 'Client',
        cell: ({ row }) => row.original.client?.company_name || '—',
    },
    {
        accessorKey: 'is_active',
        header: 'Active',
        cell: ({ row }) =>
            h(Switch, {
                checked: row.original.is_active,
                onCheckedChange: (checked: boolean) =>
                    onToggle(row.original, checked),
            }),
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) =>
            h(
                Button,
                {
                    size: 'sm',
                    variant: 'ghost',
                    onClick: () => onDelete(row.original),
                },
                h(Trash2, { class: 'h-4 w-4 text-destructive' }),
            ),
    },
];
