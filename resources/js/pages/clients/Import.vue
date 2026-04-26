<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
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
import { computed, onUnmounted, ref } from 'vue';

const props = defineProps<{
    detectedColumns?: string[];
    requiredColumns?: string[];
    optionalColumns?: string[];
    previewRows?: Array<{
        line: number;
        data: {
            company_name: string;
            contact_name: string;
            email: string;
            phone: string;
            country: string;
        };
        errors: string[];
        valid: boolean;
    }>;
    importToken?: string;
    totalRows?: number;
    errorCount?: number;
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

const initializeMapping = (): void => {
    if (props.detectedColumns && props.requiredColumns) {
        props.requiredColumns.forEach((field) => {
            const exactMatch = props.detectedColumns?.find((col) => col === field);
            const fuzzyMatch = props.detectedColumns?.find((col) => col.includes(field) || field.includes(col));
            columnMapping.value[field] = exactMatch || fuzzyMatch || '__skip__';
        });
    }
};

initializeMapping();

const uploadForm = useForm({
    file: null as File | null,
});

const handleFileUpload = (event: Event): void => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
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
            const response = await fetch('/clients/import/preview', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
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
});

const handleConfirmImport = (): void => {
    confirmForm.import_token = localImportToken.value;
    confirmForm.column_mapping = columnMapping.value;
    confirmForm.post('/clients/import/confirm');
};

onUnmounted(() => {
    abortController.value?.abort();
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Clients', href: '/clients' },
            { title: 'Import', href: '/clients/import' },
        ],
    },
});
</script>

<template>
    <Head title="Import clients" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <Heading title="Import Clients" description="Import clients from a CSV file." />
            <Button variant="outline" as-child>
                <a href="/clients/import/template" download>Download template</a>
            </Button>
        </div>

        <div class="space-y-4 rounded-md border p-4">
            <input type="file" @change="handleFileUpload" accept=".csv,.txt" :disabled="uploadForm.processing" />
            <Button @click="handlePreview" :disabled="uploadForm.processing || !uploadForm.file">Preview import</Button>
        </div>

        <div v-if="localDetectedColumns && localDetectedColumns.length > 0" class="space-y-4 rounded-md border p-4">
            <h3 class="font-medium">Map CSV columns to fields</h3>
            <div class="space-y-3">
                <div v-for="field in localRequiredColumns" :key="field" class="flex items-center gap-4">
                    <Label class="w-40">{{ field }}</Label>
                    <Select v-model="columnMapping[field]">
                        <SelectTrigger class="w-64">
                            <SelectValue placeholder="Select column" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__skip__">Skip</SelectItem>
                            <SelectItem v-for="col in localDetectedColumns" :key="col" :value="col">
                                {{ col }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
        </div>

        <div v-if="localPreviewRows && localPreviewRows.length > 0" class="space-y-4 rounded-md border p-4">
            <p class="text-sm text-muted-foreground">
                Previewing {{ localPreviewRows.length }} rows (total {{ localTotalRows }})
                <span v-if="localErrorCount > 0" class="text-destructive">, {{ localErrorCount }} errors found</span>
            </p>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Line</TableHead>
                        <TableHead>Company</TableHead>
                        <TableHead>Contact</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead>Phone</TableHead>
                        <TableHead>Country</TableHead>
                        <TableHead>Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="row in localPreviewRows" :key="row.line">
                        <TableCell>{{ row.line }}</TableCell>
                        <TableCell>{{ row.data.company_name }}</TableCell>
                        <TableCell>{{ row.data.contact_name }}</TableCell>
                        <TableCell>{{ row.data.email }}</TableCell>
                        <TableCell>{{ row.data.phone }}</TableCell>
                        <TableCell>{{ row.data.country }}</TableCell>
                        <TableCell>
                            <div v-if="row.errors.length > 0" class="text-destructive text-xs">
                                {{ row.errors.join(', ') }}
                            </div>
                            <span v-else class="text-green-600 text-xs">Valid</span>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <Button @click="handleConfirmImport" :disabled="confirmForm.processing || localErrorCount > 0">Confirm import</Button>
        </div>
    </div>
</template>
