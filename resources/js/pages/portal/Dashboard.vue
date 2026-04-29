<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { FileText, Eye, CheckCircle2, XCircle, Clock } from 'lucide-vue-next';
import { useFormat } from '@/composables/useFormat';
import { dashboard, logout } from '@/routes/portal';
import { show as showQuote } from '@/routes/portal/quotes';

const { formatCurrency } = useFormat();

const props = defineProps<{
    quotes: Array<any>;
    stats: {
        total: number;
        pending: number;
        viewed: number;
        accepted: number;
        declined: number;
    };
}>();

const getStatusBadge = (status: string): 'default' | 'outline' | 'destructive' | 'secondary' => {
    const colors: Record<string, 'default' | 'outline' | 'destructive' | 'secondary'> = {
        sent: 'outline',
        viewed: 'outline',
        accepted: 'default',
        declined: 'destructive',
    };
    return colors[status] || 'secondary';
};

const getStatusIcon = (status: string) => {
    const icons: Record<string, any> = {
        sent: Clock,
        viewed: Eye,
        accepted: CheckCircle2,
        declined: XCircle,
    };
    return icons[status] || FileText;
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
                    <div class="text-2xl font-bold">{{ stats?.total ?? 0 }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Pending</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats?.pending ?? 0 }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Viewed</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats?.viewed ?? 0 }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Accepted</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-green-600">{{ stats?.accepted ?? 0 }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Declined</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-red-600">{{ stats?.declined ?? 0 }}</div>
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
                <div v-if="quotes.length === 0" class="text-center text-muted-foreground py-8">
                    No quotes found
                </div>
                <div v-else class="space-y-4">
                    <div
                        v-for="quote in quotes"
                        :key="quote.id"
                        class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50"
                    >
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ quote.template?.name || 'Untitled Quote' }}</span>
                                <Badge :variant="getStatusBadge(quote.status || 'unknown')">
                                    {{ quote.status || 'Unknown' }}
                                </Badge>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ quote.workspace?.name || 'Unknown' }} • {{ quote.created_at ? new Date(quote.created_at).toLocaleDateString() : 'N/A' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="font-bold">{{ formatCurrency(quote.total, quote.currency ?? undefined) }}</span>
                            <Link v-if="quote.uuid" :href="showQuote(quote.uuid).url">
                                <Button variant="outline" size="sm">View</Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
