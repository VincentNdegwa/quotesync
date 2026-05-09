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

type CategoryRecord = {
    id: number;
    name: string;
    sort_order: number;
    is_active: boolean;
    created_at: string;
};

defineProps<{
    categories: CategoryRecord[];
}>();

defineOptions({
    layout: ConfigurationLayout,
});

const createOpen = ref(false);
const editOpen = ref(false);
const editingCategory = ref<CategoryRecord | null>(null);
const deleteOpen = ref(false);
const categoryToDelete = ref<CategoryRecord | null>(null);

const openEdit = (category: CategoryRecord): void => {
    editingCategory.value = category;
    editOpen.value = true;
};

const removeCategory = (category: CategoryRecord): void => {
    categoryToDelete.value = category;
    deleteOpen.value = true;
};

const executeDelete = (): void => {
    if (categoryToDelete.value) {
        router.delete(
            `/configuration/categories/${categoryToDelete.value.id}`,
            {
                preserveScroll: true,
                onSuccess: () => {
                    deleteOpen.value = false;
                    categoryToDelete.value = null;
                },
            },
        );
    }
};
</script>

<template>
    <Head title="Configuration - Categories" />

    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <Heading
                variant="small"
                title="Categories"
                description="Manage reusable categories for catalog organization."
            />
            <Button @click="createOpen = true">Create category</Button>
        </div>

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead class="text-right">Sort order</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="category in categories" :key="category.id">
                        <TableCell class="font-medium">{{
                            category.name
                        }}</TableCell>
                        <TableCell class="text-right">{{
                            category.sort_order
                        }}</TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    category.is_active ? 'default' : 'secondary'
                                "
                            >
                                {{ category.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </TableCell>
                        <TableCell class="space-x-2 text-right">
                            <Button
                                size="sm"
                                variant="outline"
                                @click="openEdit(category)"
                                >Edit</Button
                            >
                            <Button
                                size="sm"
                                variant="destructive"
                                @click="removeCategory(category)"
                                >Delete</Button
                            >
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <CreateDialog v-model:open="createOpen" />
        <EditDialog v-model:open="editOpen" :category="editingCategory" />

        <ConfirmDialog
            v-model:open="deleteOpen"
            title="Delete category"
            description="Are you sure you want to delete this category? This action cannot be undone."
            confirm-text="Delete"
            variant="destructive"
            @confirm="executeDelete"
        />
    </div>
</template>
