<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

defineProps<{
    quote: {
        id: number;
        number: string | null;
        title: string;
        status: string;
        total: number;
        currency: string | null;
        valid_until: string | null;
        created_at: string | null;
        updated_at: string | null;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Quotes',
                href: '/quotes',
            },
            {
                title: 'Quote details',
                href: '/quotes',
            },
        ],
    },
});
</script>

<template>
    <Head :title="quote.title" />

    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <Heading
                :title="quote.title"
                :description="quote.number ? `Quote ${quote.number}` : 'Quote details'"
            />

            <Button as-child>
                <Link :href="`/quotes/${quote.id}/edit`">Edit quote</Link>
            </Button>
        </div>

        <div class="grid gap-4 rounded-lg border p-4 md:grid-cols-2">
            <div>
                <p class="text-xs text-muted-foreground">Status</p>
                <Badge variant="outline" class="mt-1">{{ quote.status }}</Badge>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Total</p>
                <p class="mt-1 text-sm font-semibold">{{ quote.total.toFixed(2) }} {{ quote.currency || '' }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Valid until</p>
                <p class="mt-1 text-sm">{{ quote.valid_until || '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Last updated</p>
                <p class="mt-1 text-sm">{{ quote.updated_at || '—' }}</p>
            </div>
        </div>
    </div>
</template>
