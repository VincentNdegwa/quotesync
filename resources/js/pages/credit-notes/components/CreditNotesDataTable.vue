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
import type { CreditNoteListRecord, CreditNoteStatusEnum } from '@/types';
import { getCreditNoteColumns } from './columns';
import { creditNotesDataTableTheme } from './theme';

const props = defineProps<{
    data: CreditNoteListRecord[];
    creditNoteStatuses: CreditNoteStatusEnum[];
}>();

const emit = defineEmits<{
    delete: [creditNoteId: number];
}>();

const sorting = ref<SortingState>([]);

const columns = computed(() => getCreditNoteColumns({
    onDelete: (creditNoteId) => emit('delete', creditNoteId),
    creditNoteStatuses: props.creditNoteStatuses,
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
    <div :class="creditNotesDataTableTheme.container">
        <Table>
            <TableHeader>
                <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                    <TableHead
                        v-for="header in headerGroup.headers"
                        :key="header.id"
                        :class="creditNotesDataTableTheme.headerCell"
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
                    <TableCell :colspan="columns.length" :class="creditNotesDataTableTheme.emptyCell">
                        No credit notes found.
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
