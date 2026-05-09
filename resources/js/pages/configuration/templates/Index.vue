<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
import ConfigurationLayout from '@/layouts/configuration/Layout.vue';
import type { Paginator, QuoteTemplateRecord } from '@/types';

type Filters = {
    search: string;
    is_active: string;
};

const props = defineProps<{
    filters: Filters;
    templates: Paginator<QuoteTemplateRecord>;
}>();

defineOptions({
    layout: ConfigurationLayout,
});

const ALL = '__all__';

const query = ref({
    search: props.filters.search ?? '',
    is_active: props.filters.is_active || ALL,
});

let handle: ReturnType<typeof setTimeout> | null = null;

watch(
    () => query.value,
    () => {
        if (handle) {
            clearTimeout(handle);
        }

        handle = setTimeout(() => {
            router.get(
                '/configuration/templates',
                {
                    search: query.value.search,
                    is_active: query.value.is_active === ALL ? '' : query.value.is_active,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        }, 250);
    },
    { deep: true },
);

const deleteOpen = ref(false);
const templateToDelete = ref<QuoteTemplateRecord | null>(null);

const removeTemplate = (template: QuoteTemplateRecord): void => {
    if (template.is_system) {
        return;
    }

    templateToDelete.value = template;
    deleteOpen.value = true;
};

const executeDelete = (): void => {
    if (templateToDelete.value) {
        router.delete(`/quote-templates/${templateToDelete.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                deleteOpen.value = false;
                templateToDelete.value = null;
            },
        });
    }
};

const hasTemplates = computed(() => props.templates.data.length > 0);
</script>

<template>
    <Head title="Configuration - Templates" />

    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <Heading
                variant="small"
                title="Templates"
                description="Build reusable quote skeletons for teams and future system-template management."
            />
            <Button as-child>
                <Link href="/quote-templates/create">Create template</Link>
            </Button>
        </div>

        <div class="rounded-lg border p-3">
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <Input v-model="query.search" placeholder="Search template name or industry" class="w-full md:w-80" />

                <Select v-model="query.is_active">
                    <SelectTrigger class="w-full md:w-44">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL">All statuses</SelectItem>
                        <SelectItem value="true">Active</SelectItem>
                        <SelectItem value="false">Inactive</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div class="rounded-lg border" v-if="hasTemplates">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Industry</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Usage</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="template in templates.data" :key="template.id">
                        <TableCell class="font-medium">
                            {{ template.name }}
                            <p v-if="template.description" class="text-xs text-muted-foreground">
                                {{ template.description }}
                            </p>
                        </TableCell>
                        <TableCell>{{ template.industry || '—' }}</TableCell>
                        <TableCell>
                            <div class="flex items-center gap-2">
                                <Badge :variant="template.is_active ? 'default' : 'secondary'">
                                    {{ template.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                                <Badge v-if="template.is_system" variant="outline">System</Badge>
                            </div>
                        </TableCell>
                        <TableCell class="text-right">{{ template.usage_count }}</TableCell>
                        <TableCell class="text-right space-x-2">
                            <Button size="sm" variant="outline" as-child>
                                <Link :href="`/quote-templates/${template.id}/edit`">Edit</Link>
                            </Button>
                            <Button
                                size="sm"
                                variant="destructive"
                                :disabled="template.is_system"
                                @click="removeTemplate(template)"
                            >
                                Delete
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div v-else class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
            No templates yet. Create one to speed up recurring quote workflows.
        </div>

        <div class="flex w-full flex-wrap items-center justify-end gap-2" v-if="templates.links.length > 1">
            <template v-for="(link, index) in templates.links" :key="`${link.label}-${index}`">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="inline-flex h-9 items-center rounded-md border px-3 text-sm"
                    :class="
                        link.active
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'bg-background hover:bg-accent'
                    "
                >
                    {{
                        index === 0
                            ? 'Previous'
                            : index === templates.links.length - 1
                              ? 'Next'
                              : link.label
                    }}
                </Link>
                <span v-else class="inline-flex h-9 items-center rounded-md border px-3 text-sm text-muted-foreground">
                    {{
                        index === 0
                            ? 'Previous'
                            : index === templates.links.length - 1
                              ? 'Next'
                              : link.label
                    }}
                </span>
            </template>
        </div>

        <ConfirmDialog
            v-model:open="deleteOpen"
            title="Delete template"
            description="Are you sure you want to delete this template? This action cannot be undone."
            confirm-text="Delete"
            variant="destructive"
            @confirm="executeDelete"
        />
    </div>
</template>
