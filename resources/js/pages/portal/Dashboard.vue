<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Eye, CheckCircle2, XCircle, Clock } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useFormat } from '@/composables/useFormat';
import { show as showQuote } from '@/routes/portal/quotes';

const { formatCurrency, formatDate } = useFormat();

defineProps<{
    quotes: Array<any>;
    stats: {
        total: number;
        pending: number;
        viewed: number;
        accepted: number;
        declined: number;
    };
}>();

const getStatusBadge = (
    status: string,
): 'default' | 'outline' | 'destructive' | 'secondary' => {
    const colors: Record<
        string,
        'default' | 'outline' | 'destructive' | 'secondary'
    > = {
        sent: 'outline',
        viewed: 'outline',
        accepted: 'default',
        declined: 'destructive',
    };

    return colors[status] ?? 'secondary';
};

const _getStatusIcon = (status: string): Component => {
    const icons: Record<string, Component> = {
        sent: Clock,
        viewed: Eye,
        accepted: CheckCircle2,
        declined: XCircle,
    };

    return icons[status] ?? CircleHelp;
};
</script>

<template>
    <Head title="My Quotes" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">My Quotes</h1>
        </div>

        <!-- Stats Cards -->
        <div class="grid gap-4 md:grid-cols-5">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Total</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{ stats?.total ?? 0 }}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Pending</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{ stats?.pending ?? 0 }}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Viewed</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{ stats?.viewed ?? 0 }}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Accepted</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-green-600">
                        {{ stats?.accepted ?? 0 }}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Declined</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-red-600">
                        {{ stats?.declined ?? 0 }}
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Quotes List -->
        <Card>
            <CardHeader>
                <CardTitle>All Quotes</CardTitle>
                <CardDescription>View and manage your quotes</CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="quotes.length === 0"
                    class="py-8 text-center text-muted-foreground"
                >
                    No quotes found
                </div>
                <div v-else class="space-y-4">
                    <div
                        v-for="quote in quotes"
                        :key="quote.id"
                        class="flex items-center justify-between rounded-lg border p-4 hover:bg-gray-50"
                    >
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{
                                    quote?.title || 'Untitled Quote'
                                }}</span>
                                <Badge
                                    :variant="
                                        getStatusBadge(
                                            quote.status || 'unknown',
                                        )
                                    "
                                >
                                    {{ quote.status || 'Unknown' }}
                                </Badge>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ quote.workspace?.name || 'Unknown' }} •
                                {{ formatDate(quote.created_at) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="font-bold">{{
                                formatCurrency(
                                    quote.total,
                                    quote.currency ?? undefined,
                                )
                            }}</span>
                            <Link
                                v-if="quote.uuid"
                                :href="showQuote(quote.uuid).url"
                            >
                                <Button variant="outline" size="sm"
                                    >View</Button
                                >
                            </Link>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
