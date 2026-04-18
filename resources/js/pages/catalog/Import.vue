<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

defineProps<{
    previewRows?: Array<{
        name: string;
        sku: string;
        unit: string;
        unit_price: number;
        cost_price: number;
        tax_rate: number;
    }>;
    importToken?: string;
    totalRows?: number;
}>();

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
        <Heading title="Import Catalog" description="Upload a CSV, preview mapped rows, and confirm import." />

        <Form action="/catalog/import/preview" method="post" #default="{ processing }" class="space-y-4 rounded-md border p-4">
            <input type="file" name="file" accept=".csv,.txt" required />
            <Button type="submit" :disabled="processing">Preview import</Button>
        </Form>

        <div v-if="previewRows && previewRows.length > 0" class="space-y-4 rounded-md border p-4">
            <p class="text-sm text-muted-foreground">Previewing {{ previewRows.length }} rows (total {{ totalRows }})</p>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>SKU</TableHead>
                        <TableHead>Unit</TableHead>
                        <TableHead class="text-right">Unit price</TableHead>
                        <TableHead class="text-right">Cost price</TableHead>
                        <TableHead class="text-right">Tax rate</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="(row, index) in previewRows" :key="index">
                        <TableCell>{{ row.name }}</TableCell>
                        <TableCell>{{ row.sku || '—' }}</TableCell>
                        <TableCell>{{ row.unit }}</TableCell>
                        <TableCell class="text-right">{{ row.unit_price }}</TableCell>
                        <TableCell class="text-right">{{ row.cost_price }}</TableCell>
                        <TableCell class="text-right">{{ row.tax_rate }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <Form action="/catalog/import/confirm" method="post" #default="{ processing }">
                <input type="hidden" name="import_token" :value="importToken" />
                <Button type="submit" :disabled="processing">Confirm import</Button>
            </Form>
        </div>
    </div>
</template>
