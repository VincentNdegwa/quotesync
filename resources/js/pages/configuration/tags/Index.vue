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

type TagRecord = {
    id: number;
    name: string;
    is_active: boolean;
    created_at: string;
};

defineProps<{
    tags: TagRecord[];
}>();

defineOptions({
    layout: ConfigurationLayout,
});

const createOpen = ref(false);
const editOpen = ref(false);
const editingTag = ref<TagRecord | null>(null);
const deleteOpen = ref(false);
const tagToDelete = ref<TagRecord | null>(null);

const openEdit = (tag: TagRecord): void => {
    editingTag.value = tag;
    editOpen.value = true;
};

const removeTag = (tag: TagRecord): void => {
    tagToDelete.value = tag;
    deleteOpen.value = true;
};

const executeDelete = (): void => {
    if (tagToDelete.value) {
        router.delete(`/configuration/tags/${tagToDelete.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                deleteOpen.value = false;
                tagToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <Head title="Configuration - Tags" />

    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <Heading
                variant="small"
                title="Tags"
                description="Manage reusable tags used in clients and catalog records."
            />
            <Button @click="createOpen = true">Create tag</Button>
        </div>

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="tag in tags" :key="tag.id">
                        <TableCell class="font-medium">{{ tag.name }}</TableCell>
                        <TableCell>
                            <Badge :variant="tag.is_active ? 'default' : 'secondary'">
                                {{ tag.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-right space-x-2">
                            <Button size="sm" variant="outline" @click="openEdit(tag)">Edit</Button>
                            <Button size="sm" variant="destructive" @click="removeTag(tag)">Delete</Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <CreateDialog v-model:open="createOpen" />
        <EditDialog v-model:open="editOpen" :tag="editingTag" />

        <ConfirmDialog
            v-model:open="deleteOpen"
            title="Delete tag"
            description="Are you sure you want to delete this tag? This action cannot be undone."
            confirm-text="Delete"
            variant="destructive"
            @confirm="executeDelete"
        />
    </div>
</template>
