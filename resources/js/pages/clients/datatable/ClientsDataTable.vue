<script setup lang="ts">
import {
    FlexRender,
    getCoreRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table';
import type { RowSelectionState, SortingState } from '@tanstack/vue-table';
import { computed, ref, watch } from 'vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { valueUpdater } from '@/components/ui/table/utils';
import type { ClientRecord } from '@/pages/clients/types';
import { getClientColumns } from './columns';
import { clientsDataTableTheme } from './theme';

const props = defineProps<{
    data: ClientRecord[];
}>();

const emit = defineEmits<{
    edit: [client: ClientRecord];
    'update:selectedIds': [ids: number[]];
}>();

const sorting = ref<SortingState>([]);
const rowSelection = ref<RowSelectionState>({});

const columns = computed(() =>
    getClientColumns({
        onEdit: (client) => emit('edit', client),
    }),
);

const table = useVueTable({
    get data() {
        return props.data;
    },
    get columns() {
        return columns.value;
    },
    state: {
        get sorting() {
            return sorting.value;
        },
        get rowSelection() {
            return rowSelection.value;
        },
    },
    getRowId: (row) => String(row.id),
    enableRowSelection: true,
    onSortingChange: (updater) => valueUpdater(updater, sorting),
    onRowSelectionChange: (updater) => valueUpdater(updater, rowSelection),
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
});

watch(
    () => [rowSelection.value, props.data],
    () => {
        const selectedIds = table
            .getSelectedRowModel()
            .rows.map((row) => row.original.id);
        emit('update:selectedIds', selectedIds);
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <div :class="clientsDataTableTheme.container">
        <Table>
            <TableHeader>
                <TableRow
                    v-for="headerGroup in table.getHeaderGroups()"
                    :key="headerGroup.id"
                >
                    <TableHead
                        v-for="header in headerGroup.headers"
                        :key="header.id"
                        :class="clientsDataTableTheme.headerCell"
                    >
                        <FlexRender
                            v-if="!header.isPlaceholder"
                            :render="header.column.columnDef.header"
                            :props="header.getContext()"
                        />
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <template v-if="table.getRowModel().rows?.length">
                    <TableRow
                        v-for="row in table.getRowModel().rows"
                        :key="row.id"
                        :data-state="
                            row.getIsSelected() ? 'selected' : undefined
                        "
                    >
                        <TableCell
                            v-for="cell in row.getVisibleCells()"
                            :key="cell.id"
                        >
                            <FlexRender
                                :render="cell.column.columnDef.cell"
                                :props="cell.getContext()"
                            />
                        </TableCell>
                    </TableRow>
                </template>
                <TableRow v-else>
                    <TableCell
                        :colspan="columns.length"
                        :class="clientsDataTableTheme.emptyCell"
                    >
                        No results found.
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
