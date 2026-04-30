<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import ConfigurationLayout from '@/layouts/configuration/Layout.vue';
import CreateDialog from './components/CreateDialog.vue';
import EditDialog from './components/EditDialog.vue';

type TaxRecord = {
    id: number;
    name: string;
    rate: number | string;
    is_default: boolean;
    is_active: boolean;
    created_at: string;
};

defineProps<{
    taxes: TaxRecord[];
}>();

defineOptions({
    layout: ConfigurationLayout,
});

const createOpen = ref(false);
const editOpen = ref(false);
const editingTax = ref<TaxRecord | null>(null);
const deleteOpen = ref(false);
const taxToDelete = ref<TaxRecord | null>(null);

const openEdit = (tax: TaxRecord): void => {
    editingTax.value = tax;
    editOpen.value = true;
};

const removeTax = (tax: TaxRecord): void => {
    taxToDelete.value = tax;
    deleteOpen.value = true;
};

const executeDelete = (): void => {
    if (taxToDelete.value) {
        router.delete(`/configuration/taxes/${taxToDelete.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                deleteOpen.value = false;
                taxToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <Head title="Configuration - Taxes" />

    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <Heading
                variant="small"
                title="Taxes"
                description="Manage tax presets used in catalog items and quotes."
            />
            <Button @click="createOpen = true">Create tax</Button>
        </div>

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead class="text-right">Rate %</TableHead>
                        <TableHead>Default</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="tax in taxes" :key="tax.id">
                        <TableCell class="font-medium">{{ tax.name }}</TableCell>
                        <TableCell class="text-right">{{ tax.rate }}</TableCell>
                        <TableCell>
                            <Badge :variant="tax.is_default ? 'default' : 'secondary'">
                                {{ tax.is_default ? 'Default' : 'No' }}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            <Badge :variant="tax.is_active ? 'default' : 'secondary'">
                                {{ tax.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-right space-x-2">
                            <Button size="sm" variant="outline" @click="openEdit(tax)">Edit</Button>
                            <Button size="sm" variant="destructive" @click="removeTax(tax)">Delete</Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <CreateDialog v-model:open="createOpen" />
        <EditDialog v-model:open="editOpen" :tax="editingTax" />

        <ConfirmDialog
            v-model:open="deleteOpen"
            title="Delete tax"
            description="Are you sure you want to delete this tax? This action cannot be undone."
            confirm-text="Delete"
            variant="destructive"
            @confirm="executeDelete"
        />
    </div>
</template>
