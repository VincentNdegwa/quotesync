<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import ConfigurationLayout from '@/layouts/configuration/Layout.vue';

defineProps<{
    template: {
        id: number;
        name: string;
        description: string | null;
        industry: string | null;
        is_active: boolean;
        is_system: boolean;
        usage_count: number;
        updated_at: string | null;
    };
}>();

defineOptions({
    layout: ConfigurationLayout,
});
</script>

<template>
    <Head :title="template.name" />

    <div class="space-y-4">
        <div class="flex items-center justify-between gap-2">
            <Heading
                variant="small"
                :title="template.name"
                :description="template.description || undefined"
            />

            <Button as-child>
                <Link :href="`/quote-templates/${template.id}/edit`">Edit template</Link>
            </Button>
        </div>

        <div class="grid gap-4 rounded-lg border p-4 md:grid-cols-2">
            <div>
                <p class="text-xs text-muted-foreground">Industry</p>
                <p class="mt-1 text-sm">{{ template.industry || '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Usage count</p>
                <p class="mt-1 text-sm">{{ template.usage_count }}</p>
            </div>
            <div class="flex items-center gap-2">
                <Badge :variant="template.is_active ? 'default' : 'secondary'">
                    {{ template.is_active ? 'Active' : 'Inactive' }}
                </Badge>
                <Badge v-if="template.is_system" variant="outline">System</Badge>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Updated</p>
                <p class="mt-1 text-sm">{{ template.updated_at || '—' }}</p>
            </div>
        </div>
    </div>
</template>
