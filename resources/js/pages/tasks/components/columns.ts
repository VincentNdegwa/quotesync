import type { ColumnDef } from '@tanstack/vue-table';
import { ArrowUpDown } from 'lucide-vue-next';
import { h } from 'vue';
import type { VNode } from 'vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { useFormat } from '@/composables/useFormat';
import type { TaskModel } from '@/types/models';
import TaskTableRowActions from './TaskTableRowActions.vue';

type TaskListRecord = TaskModel;

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

export const sortableHeader = (
    label: string,
    column: {
        getIsSorted: () => false | 'asc' | 'desc';
        toggleSorting: (desc?: boolean) => void;
    },
    align: 'left' | 'right' = 'left',
): VNode =>
    h(
        Button,
        {
            variant: 'ghost',
            class:
                align === 'right'
                    ? 'h-8 w-full justify-center px-0 text-right'
                    : 'h-8 justify-center px-0 text-left',
            onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
        },
        () => [label, h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
    );

export const getTaskColumns = (
    options: TaskColumnOptions,
): ColumnDef<TaskListRecord>[] => {
    const columns: ColumnDef<TaskListRecord>[] = [
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
            accessorKey: 'title',
            header: ({ column }) => sortableHeader('Title', column),
            cell: ({ row }) =>
                h('span', { class: 'font-medium' }, row.original.title),
        },
        {
            accessorKey: 'taskable',
            header: 'Related to',
            cell: ({ row }): VNode => {
                const taskable = row.original.taskable;

                if (!taskable) {
                    return h('span', { class: 'text-muted-foreground' }, '—');
                }

                const type = row.original.taskable_type.split('\\').pop();
                const identifier =
                    taskable.number ||
                    taskable.title ||
                    taskable.company_name ||
                    `#${taskable.id}`;

                return h(
                    'span',
                    { class: 'text-sm text-muted-foreground' },
                    `${type}: ${identifier}`,
                );
            },
        },
        {
            accessorKey: 'assigned_to',
            header: 'Assigned to',
            cell: ({ row }): VNode => {
                const assignedTo = row.original.assigned_to;

                if (!assignedTo) {
                    return h('span', { class: 'text-muted-foreground' }, '—');
                }

                const initials = assignedTo.name
                    .split(' ')
                    .map((n) => n[0])
                    .join('')
                    .toUpperCase()
                    .slice(0, 2);

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
            cell: ({ row }): VNode => {
                const status = row.original.status;

                if (!status) {
                    return h('span', { class: 'text-muted-foreground' }, '—');
                }

                return h(
                    Badge,
                    {
                        variant: 'outline',
                        class: ['px-3 py-1 text-xs font-semibold'],
                        style: {
                            borderColor: status.color,
                            color: status.color,
                        },
                    },
                    () => status.name,
                );
            },
        },
        {
            accessorKey: 'due_date',
            header: ({ column }) => sortableHeader('Due date', column),
            cell: ({ row }): VNode => {
                const formatted = useFormat().formatDate(row.original.due_date);

                return h('span', formatted || '—');
            },
        },
    ];

    columns.push({
        id: 'actions',
        enableSorting: false,
        header: () => h('div', { class: 'w-full text-right' }, 'Actions'),
        cell: ({ row }) =>
            h(
                'div',
                { class: 'text-right' },
                h(TaskTableRowActions, {
                    task: row.original as TaskModel,
                    onEdit: (taskId: number) => options.onEdit(taskId),
                    onDelete: (taskId: number) => options.onDelete(taskId),
                }),
            ),
    });

    return columns;
};
