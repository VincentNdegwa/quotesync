<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { onUnmounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFormat } from '@/composables/useFormat';
import type { ConfigurationUnitRecord } from '@/types';

const props = defineProps<{
    detectedColumns?: string[];
    requiredColumns?: string[];
    optionalColumns?: string[];
    previewRows?: Array<{
        line: number;
        data: {
            name: string;
            sku: string;
            unit: string;
            unit_price: number;
            cost_price: number;
        };
        errors: string[];
        valid: boolean;
    }>;
    importToken?: string;
    totalRows?: number;
    errorCount?: number;
    units: ConfigurationUnitRecord[];
    skippedItems?: Array<{
        line: number;
        name: string;
        sku: string;
        reason: string;
    }>;
}>();

const localDetectedColumns = ref<string[]>(props.detectedColumns ?? []);
const localRequiredColumns = ref<string[]>(props.requiredColumns ?? []);
const localOptionalColumns = ref<string[]>(props.optionalColumns ?? []);
const localPreviewRows = ref(props.previewRows ?? []);
const localImportToken = ref(props.importToken ?? '');
const localTotalRows = ref(props.totalRows ?? 0);
const localErrorCount = ref(props.errorCount ?? 0);

const abortController = ref<AbortController | null>(null);

const columnMapping = ref<Record<string, string>>({});

// Unit mapping state
const unitMappingMode = ref<'all' | 'individual'>('all');
const unitForAll = ref(props.units.length > 0 ? props.units[0].name : '');
const unitMapping = ref<Record<number, string>>({});

const initializeMapping = (): void => {
    if (props.detectedColumns && props.requiredColumns) {
        props.requiredColumns.forEach((field) => {
            const exactMatch = props.detectedColumns?.find(
                (col) => col === field,
            );
            const fuzzyMatch = props.detectedColumns?.find(
                (col) => col.includes(field) || field.includes(col),
            );
            columnMapping.value[field] = exactMatch || fuzzyMatch || '__skip__';
        });
    }
};

const initializeUnitMapping = (): void => {
    if (unitMappingMode.value === 'all') {
        unitForAll.value = props.units.length > 0 ? props.units[0].name : '';
    } else {
        localPreviewRows.value.forEach((row) => {
            if (!unitMapping.value[row.line]) {
                unitMapping.value[row.line] =
                    row.data.unit ||
                    (props.units.length > 0 ? props.units[0].name : '');
            }
        });
    }
};

initializeMapping();

const { formatCurrency } = useFormat(
    (usePage().props.workspace_currency as string) || undefined,
);

const uploadForm = useForm({
    file: null as File | null,
});

const handleFileUpload = (event: Event): void => {
    const target = event.target as HTMLInputElement;

    if (target.files[0]) {
        // eslint-disable-line @typescript-eslint/no-unnecessary-condition
        uploadForm.file = target.files[0];
    }
};

const handlePreview = async (): Promise<void> => {
    if (uploadForm.file) {
        abortController.value?.abort();
        abortController.value = new AbortController();

        const formData = new FormData();
        formData.append('file', uploadForm.file);

        try {
            const response = await fetch('/catalog/import/preview', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') || '',
                    Accept: 'application/json',
                },
                body: formData,
                signal: abortController.value.signal,
            });

            if (!response.ok) {
                throw new Error('Preview failed');
            }

            const data = await response.json();

            if (abortController.value.signal.aborted) {
                return;
            }

            localDetectedColumns.value = data.detectedColumns;
            localRequiredColumns.value = data.requiredColumns;
            localOptionalColumns.value = data.optionalColumns;
            localPreviewRows.value = data.previewRows;
            localImportToken.value = data.importToken;
            localTotalRows.value = data.totalRows;
            localErrorCount.value = data.errorCount;

            initializeMapping();
            initializeUnitMapping();
        } catch (error) {
            if (error instanceof Error && error.name !== 'AbortError') {
                console.error('Preview failed:', error);
            }
        }
    }
};

const confirmForm = useForm({
    import_token: '',
    column_mapping: {} as Record<string, string>,
    unit_mapping_mode: 'all' as 'all' | 'individual',
    unit_for_all: '',
    unit_mapping: {} as Record<number, string>,
});

const handleConfirmImport = (): void => {
    confirmForm.import_token = localImportToken.value;
    confirmForm.column_mapping = columnMapping.value;
    confirmForm.unit_mapping_mode = unitMappingMode.value;
    confirmForm.unit_for_all = unitForAll.value;
    confirmForm.unit_mapping = unitMapping.value;
    confirmForm.post('/catalog/import/confirm');
};

onUnmounted(() => {
    abortController.value?.abort();
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Catalog', href: '/catalog' },
            { title: 'Import', href: '/catalog/import' },
        ],
    },
});
</script>

<template>
    <Head title="Import catalog" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                title="Import Catalog Items"
                description="Import catalog items from a CSV file."
            />

            <Button variant="outline" as-child>
                <a href="/catalog/import/template" download
                    >Download template</a
                >
            </Button>
        </div>

        <div class="space-y-4 rounded-md border p-4">
            <input
                type="file"
                @change="handleFileUpload"
                accept=".csv,.txt"
                :disabled="uploadForm.processing"
            />
            <Button
                @click="handlePreview"
                :disabled="uploadForm.processing || !uploadForm.file"
                >Preview import</Button
            >
        </div>

        <div
            v-if="localDetectedColumns && localDetectedColumns.length > 0"
            class="space-y-4 rounded-md border p-4"
        >
            <h3 class="font-medium">Map CSV columns to fields</h3>
            <div class="space-y-3">
                <div
                    v-for="field in localRequiredColumns"
                    :key="field"
                    class="flex items-center gap-4"
                >
                    <Label class="w-40">{{ field }}</Label>
                    <Select v-model="columnMapping[field]">
                        <SelectTrigger class="w-64">
                            <SelectValue placeholder="Select column" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__skip__">Skip</SelectItem>
                            <SelectItem
                                v-for="col in localDetectedColumns"
                                :key="col"
                                :value="col"
                            >
                                {{ col }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
        </div>

        <div
            v-if="localPreviewRows && localPreviewRows.length > 0"
            class="space-y-4 rounded-md border p-4"
        >
            <h3 class="font-medium">Map units</h3>
            <div class="space-y-3">
                <div class="flex items-center gap-4">
                    <Label class="w-40">Mapping mode</Label>
                    <Select v-model="unitMappingMode">
                        <SelectTrigger class="w-64">
                            <SelectValue placeholder="Select mapping mode" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all"
                                >Apply one unit to all items</SelectItem
                            >
                            <SelectItem value="individual"
                                >Select unit per item</SelectItem
                            >
                        </SelectContent>
                    </Select>
                </div>

                <div
                    v-if="unitMappingMode === 'all'"
                    class="flex items-center gap-4"
                >
                    <Label class="w-40">Unit for all</Label>
                    <Select v-model="unitForAll">
                        <SelectTrigger class="w-64">
                            <SelectValue placeholder="Select unit" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="unit in units"
                                :key="unit.id"
                                :value="unit.name"
                            >
                                {{ unit.name
                                }}{{ unit.symbol ? ` (${unit.symbol})` : '' }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
        </div>

        <div
            v-if="localPreviewRows && localPreviewRows.length > 0"
            class="space-y-4 rounded-md border p-4"
        >
            <p class="text-sm text-muted-foreground">
                Previewing {{ localPreviewRows.length }} rows (total
                {{ localTotalRows }})
                <span v-if="localErrorCount > 0" class="text-destructive"
                    >, {{ localErrorCount }} errors found</span
                >
            </p>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Line</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>SKU</TableHead>
                        <TableHead v-if="unitMappingMode === 'individual'"
                            >Unit</TableHead
                        >
                        <TableHead class="text-right">Unit price</TableHead>
                        <TableHead class="text-right">Cost price</TableHead>
                        <TableHead>Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="row in localPreviewRows" :key="row.line">
                        <TableCell>{{ row.line }}</TableCell>
                        <TableCell>{{ row.data.name }}</TableCell>
                        <TableCell>{{ row.data.sku }}</TableCell>
                        <TableCell v-if="unitMappingMode === 'individual'">
                            <Select
                                v-model="unitMapping[row.line]"
                                class="w-40"
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select unit" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="unit in units"
                                        :key="unit.id"
                                        :value="unit.name"
                                    >
                                        {{ unit.name
                                        }}{{
                                            unit.symbol
                                                ? ` (${unit.symbol})`
                                                : ''
                                        }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </TableCell>
                        <TableCell class="text-right">{{
                            formatCurrency(row.data.unit_price)
                        }}</TableCell>
                        <TableCell class="text-right">{{
                            formatCurrency(row.data.cost_price)
                        }}</TableCell>
                        <TableCell>
                            <div
                                v-if="row.errors.length > 0"
                                class="text-xs text-destructive"
                            >
                                {{ row.errors.join(', ') }}
                            </div>
                            <span v-else class="text-xs text-green-600"
                                >Valid</span
                            >
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <Button
                @click="handleConfirmImport"
                :disabled="confirmForm.processing || localErrorCount > 0"
                >Confirm import</Button
            >
        </div>

        <div
            v-if="props.skippedItems && props.skippedItems.length > 0"
            class="space-y-4 rounded-md border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20"
        >
            <h3 class="font-medium text-yellow-800 dark:text-yellow-200">
                Skipped items ({{ props.skippedItems.length }})
            </h3>
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Line</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>SKU</TableHead>
                        <TableHead>Reason</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="item in props.skippedItems"
                        :key="item.line"
                        class="bg-yellow-100/50 dark:bg-yellow-800/30"
                    >
                        <TableCell>{{ item.line }}</TableCell>
                        <TableCell>{{ item.name }}</TableCell>
                        <TableCell>{{ item.sku }}</TableCell>
                        <TableCell
                            class="text-yellow-700 dark:text-yellow-300"
                            >{{ item.reason }}</TableCell
                        >
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>
