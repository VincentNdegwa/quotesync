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
        company_name: string;
        contact_name: string;
        email: string;
        phone: string;
        country: string;
    }>;
    importToken?: string;
    totalRows?: number;
}>();

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
        <Heading title="Import Clients" description="Upload a CSV, preview data, then confirm the import." />

        <Form action="/clients/import/preview" method="post" #default="{ processing }" class="space-y-4 rounded-md border p-4">
            <input type="file" name="file" accept=".csv,.txt" required />
            <Button type="submit" :disabled="processing">Preview import</Button>
        </Form>

        <div v-if="previewRows && previewRows.length > 0" class="space-y-4 rounded-md border p-4">
            <p class="text-sm text-muted-foreground">Previewing {{ previewRows.length }} rows (total {{ totalRows }})</p>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Company</TableHead>
                        <TableHead>Contact</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead>Phone</TableHead>
                        <TableHead>Country</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="(row, index) in previewRows" :key="index">
                        <TableCell>{{ row.company_name }}</TableCell>
                        <TableCell>{{ row.contact_name || '—' }}</TableCell>
                        <TableCell>{{ row.email || '—' }}</TableCell>
                        <TableCell>{{ row.phone || '—' }}</TableCell>
                        <TableCell>{{ row.country || '—' }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <Form action="/clients/import/confirm" method="post" #default="{ processing }">
                <input type="hidden" name="import_token" :value="importToken" />
                <Button type="submit" :disabled="processing">Confirm import</Button>
            </Form>
        </div>
    </div>
</template>
