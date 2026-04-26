<script setup lang="ts">
import {
    FlexRender,
    getCoreRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table';
import type { SortingState } from '@tanstack/vue-table';
import { computed, ref } from 'vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { valueUpdater } from '@/components/ui/table/utils';
import type { QuoteListRecord, QuoteStatusEnum } from '@/types';
import { getQuoteColumns } from './columns';
import { quotesDataTableTheme } from './theme';

const props = defineProps<{
    data: QuoteListRecord[];
    quoteStatuses: QuoteStatusEnum[];
    isClient?: boolean;
}>();

const emit = defineEmits<{
    send: [quoteId: number];
    delete: [quoteId: number];
    approve: [quoteId: number];
    reject: [quoteId: number];
}>();

const sorting = ref<SortingState>([]);

const columns = computed(() => getQuoteColumns({
    onSend: (quoteId) => emit('send', quoteId),
    onDelete: (quoteId) => emit('delete', quoteId),
    onApprove: (quoteId) => emit('approve', quoteId),
    onReject: (quoteId) => emit('reject', quoteId),
    quoteStatuses: props.quoteStatuses,
    isClient: props.isClient ?? false,
}));

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
    },
    getRowId: (row) => String(row.id),
    onSortingChange: (updater) => valueUpdater(updater, sorting),
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
});
</script>

<template>
    <div :class="quotesDataTableTheme.container">
        <Table>
            <TableHeader>
                <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                    <TableHead
                        v-for="header in headerGroup.headers"
                        :key="header.id"
                        :class="quotesDataTableTheme.headerCell"
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
                        <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                            <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                        </TableCell>
                    </TableRow>
                </template>
                <TableRow v-else>
                    <TableCell :colspan="columns.length" :class="quotesDataTableTheme.emptyCell">
                        No quotes found.
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
