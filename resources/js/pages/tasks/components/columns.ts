import type { ColumnDef } from '@tanstack/vue-table';
import { ArrowUpDown } from 'lucide-vue-next';
import { h } from 'vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFormat } from '@/composables/useFormat';
import TaskTableRowActions from './TaskTableRowActions.vue';

type TaskListRecord = {
    id: number;
    title: string;
    description: string | null;
    due_date: string | null;
    completed_at: string | null;
    taskable_type: string;
    taskable_id: number;
    assigned_to: { id: number; name: string } | null;
    assigned_by: { id: number; name: string } | null;
    status: {
        id: number;
        name: string;
        slug: string;
        color: string;
    } | null;
    taskable: {
        id: number;
        title?: string;
        number?: string;
        company_name?: string;
    } | null;
};

type TaskColumnOptions = {
    taskStatuses: Array<{
        id: number;
        name: string;
        slug: string;
        color: string;
    }>;
    onEdit: (taskId: number) => void;
    onDelete: (taskId: number) => void;
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

export const getTaskColumns = (options: TaskColumnOptions): ColumnDef<TaskListRecord>[] => {
    const columns: ColumnDef<TaskListRecord>[] = [
        {
            accessorKey: 'title',
            header: ({ column }) => sortableHeader('Title', column),
            cell: ({ row }) => h('span', { class: 'font-medium' }, row.original.title),
        },
        {
            accessorKey: 'taskable',
            header: 'Related to',
            cell: ({ row }) => {
                const taskable = row.original.taskable;

                if (!taskable) {
return '—';
}
                
                const type = row.original.taskable_type.split('\\').pop();
                const identifier = taskable.number || taskable.title || taskable.company_name || `#${taskable.id}`;
                
                return h('span', { class: 'text-sm text-muted-foreground' }, `${type}: ${identifier}`);
            },
        },
        {
            accessorKey: 'assigned_to',
            header: 'Assigned to',
            cell: ({ row }) => {
                const assignedTo = row.original.assigned_to;

                if (!assignedTo) {
return '—';
}
                
                const initials = assignedTo.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
                
                return h('div', { class: 'flex items-center gap-2' }, [
                    h(Avatar, { class: 'h-6 w-6' }, () => [
                        h(AvatarFallback, { class: 'text-xs' }, initials),
                    ]),
                    h('span', { class: 'text-sm' }, assignedTo.name),
                ]);
            },
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => {
                const status = row.original.status;

                if (!status) {
return '—';
}
                
                return h(Badge, {
                    variant: 'outline',
                    class: ['px-3 py-1 text-xs font-semibold'],
                    style: { borderColor: status.color, color: status.color },
                }, () => status.name);
            },
        },
        {
            accessorKey: 'due_date',
            header: ({ column }) => sortableHeader('Due date', column),
            cell: ({ row }) => useFormat().formatDate(row.original.due_date) || '—',
        },
    ];

    columns.push({
        id: 'actions',
        enableSorting: false,
        header: () => h('div', { class: 'w-full text-right' }, 'Actions'),
        cell: ({ row }) => h('div', { class: 'text-right' }, h(TaskTableRowActions, {
            task: row.original,
            onEdit: (taskId: number) => options.onEdit(taskId),
            onDelete: (taskId: number) => options.onDelete(taskId),
        })),
    });

    return columns;
};
