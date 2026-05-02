<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
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

type UnitRecord = {
    id: number;
    name: string;
    symbol: string | null;
    is_active: boolean;
    created_at: string;
};

defineProps<{
    units: UnitRecord[];
}>();

defineOptions({
    layout: ConfigurationLayout,
});

const createOpen = ref(false);
const editOpen = ref(false);
const editingUnit = ref<UnitRecord | null>(null);
const deleteOpen = ref(false);
const unitToDelete = ref<UnitRecord | null>(null);

const openEdit = (unit: UnitRecord): void => {
    editingUnit.value = unit;
    editOpen.value = true;
};

const removeUnit = (unit: UnitRecord): void => {
    unitToDelete.value = unit;
    deleteOpen.value = true;
};

const executeDelete = (): void => {
    if (unitToDelete.value) {
        router.delete(`/configuration/units/${unitToDelete.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                deleteOpen.value = false;
                unitToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <Head title="Configuration - Units" />

    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <Heading
                variant="small"
                title="Units"
                description="Manage reusable measurement units used in catalog records."
            />
            <Button @click="createOpen = true">Create unit</Button>
        </div>

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Symbol</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="unit in units" :key="unit.id">
                        <TableCell class="font-medium">{{ unit.name }}</TableCell>
                        <TableCell>{{ unit.symbol || '—' }}</TableCell>
                        <TableCell>
                            <Badge :variant="unit.is_active ? 'default' : 'secondary'">
                                {{ unit.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-right space-x-2">
                            <Button size="sm" variant="outline" @click="openEdit(unit)">Edit</Button>
                            <Button size="sm" variant="destructive" @click="removeUnit(unit)">Delete</Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <CreateDialog v-model:open="createOpen" />
        <EditDialog v-model:open="editOpen" :unit="editingUnit" />

        <ConfirmDialog
            v-model:open="deleteOpen"
            title="Delete unit"
            description="Are you sure you want to delete this unit? This action cannot be undone."
            confirm-text="Delete"
            variant="destructive"
            @confirm="executeDelete"
        />
    </div>
</template>
