<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
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

type IndustryRecord = {
    id: number;
    name: string;
    description: string | null;
    icon: string | null;
    color: string | null;
    is_active: boolean;
    created_at: string;
};

defineProps<{
    industries: IndustryRecord[];
}>();

defineOptions({
    layout: ConfigurationLayout,
});

const createOpen = ref(false);
const editOpen = ref(false);
const editingIndustry = ref<IndustryRecord | null>(null);

const openEdit = (industry: IndustryRecord): void => {
    editingIndustry.value = industry;
    editOpen.value = true;
};

const removeIndustry = (industry: IndustryRecord): void => {
    router.delete(`/configuration/industries/${industry.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Configuration - Industries" />

    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <Heading
                variant="small"
                title="Industries"
                description="Manage reusable industry classifications used for clients."
            />
            <Button @click="createOpen = true">Create industry</Button>
        </div>

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Description</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="industry in industries" :key="industry.id">
                        <TableCell class="font-medium">{{ industry.name }}</TableCell>
                        <TableCell>{{ industry.description || '—' }}</TableCell>
                        <TableCell>
                            <Badge :variant="industry.is_active ? 'default' : 'secondary'">
                                {{ industry.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-right space-x-2">
                            <Button size="sm" variant="outline" @click="openEdit(industry)">Edit</Button>
                            <Button size="sm" variant="destructive" @click="removeIndustry(industry)">Delete</Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <CreateDialog v-model:open="createOpen" />
        <EditDialog v-model:open="editOpen" :industry="editingIndustry" />
    </div>
</template>
