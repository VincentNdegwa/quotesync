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
import type { InvoiceListRecord, InvoiceStatusEnum } from '@/types';
import { getInvoiceColumns } from './columns';
import { invoicesDataTableTheme } from './theme';

const props = defineProps<{
    data: InvoiceListRecord[];
    invoiceStatuses: InvoiceStatusEnum[];
}>();

const emit = defineEmits<{
    delete: [invoiceId: number];
    'update:selectedIds': [ids: number[]];
}>();

const sorting = ref<SortingState>([]);
const rowSelection = ref<RowSelectionState>({});

const columns = computed(() =>
    getInvoiceColumns({
        onDelete: (invoiceId) => emit('delete', invoiceId),
        invoiceStatuses: props.invoiceStatuses,
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
    onSortingChange: (updater) => valueUpdater(updater, sorting),
    onRowSelectionChange: (updater) => valueUpdater(updater, rowSelection),
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    enableRowSelection: true,
});

watch(
    () => [rowSelection.value, props.data],
    () => {
        const ids = table
            .getSelectedRowModel()
            .rows.map((row) => row.original.id);
        emit('update:selectedIds', ids);
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <div :class="invoicesDataTableTheme.container">
        <Table>
            <TableHeader>
                <TableRow
                    v-for="headerGroup in table.getHeaderGroups()"
                    :key="headerGroup.id"
                >
                    <TableHead
                        v-for="header in headerGroup.headers"
                        :key="header.id"
                        :class="invoicesDataTableTheme.headerCell"
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
                        :class="invoicesDataTableTheme.emptyCell"
                    >
                        No invoices found.
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
