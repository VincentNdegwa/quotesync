<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useEnums } from '@/composables/useEnums';
import { useFormat } from '@/composables/useFormat';
import { BarChart, Eye, Timer, Users } from 'lucide-vue-next';

type AnalyticsData = {
    total_views: number;
    unique_visitors: number;
    avg_time_spent_seconds: number;
    max_scroll_depth_percent: number;
    section_views: Array<{ section: string; count: number }>;
    scroll_heatmap: Array<{ depth: number; count: number }>;
    timeline: Array<{ date: string; views: number }>;
};

const props = defineProps<{
    quote: { id: number; number: string; title: string; status: string };
    analytics: AnalyticsData;
}>();

const breadcrumbs = computed(() => [
    { title: 'Quotes', href: '/quotes' },
    { title: props.quote?.title ?? 'Quote', href: `/quotes/${props.quote.id}` },
    { title: 'Analytics', href: '#' },
]);

const { getQuoteStatus } = useEnums();
const { formatCurrency: fmt, formatDate: fmtDate } = useFormat();

const formatTime = (seconds: number): string => {
    if (seconds < 60) return `${seconds}s`;
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return secs > 0 ? `${mins}m ${secs}s` : `${mins}m`;
};

const maxHeatmapCount = computed(() => {
    return Math.max(...props.analytics.scroll_heatmap.map((h) => h.count), 1);
});
</script>

<template>
    <Head :title="`Analytics - ${quote.title}`" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <Heading
                    variant="small"
                    :title="`Analytics: ${quote.title}`"
                    :description="`Quote #${quote.number} engagement metrics`"
                />
                <div class="mt-2 flex items-center gap-2">
                    <Badge :variant="getQuoteStatus(quote.status)?.value === 'accepted' ? 'default' : 'secondary'">
                        {{ getQuoteStatus(quote.status)?.label }}
                    </Badge>
                </div>
            </div>
            <Link :href="`/quotes/${quote.id}`">
                <Button variant="outline">Back to quote</Button>
            </Link>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Total Views</CardTitle>
                    <Eye class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ analytics.total_views }}</div>
                    <p class="text-xs text-muted-foreground">Page loads</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Unique Visitors</CardTitle>
                    <Users class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ analytics.unique_visitors }}</div>
                    <p class="text-xs text-muted-foreground">Distinct IPs</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Avg Time Spent</CardTitle>
                    <Timer class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ formatTime(Math.round(analytics.avg_time_spent_seconds)) }}</div>
                    <p class="text-xs text-muted-foreground">Per session</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Max Scroll Depth</CardTitle>
                    <BarChart class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ analytics.max_scroll_depth_percent }}%</div>
                    <p class="text-xs text-muted-foreground">Deepest point</p>
                </CardContent>
            </Card>
        </div>

        <!-- Scroll heatmap -->
        <Card>
            <CardHeader>
                <CardTitle>Scroll Heatmap</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-2">
                    <div
                        v-for="bucket in analytics.scroll_heatmap"
                        :key="bucket.depth"
                        class="flex items-center gap-2"
                    >
                        <span class="w-12 text-xs text-muted-foreground">{{ bucket.depth }}%</span>
                        <div class="flex-1 h-4 rounded-md bg-muted overflow-hidden">
                            <div
                                class="h-full bg-primary transition-all"
                                :style="{ width: `${(bucket.count / maxHeatmapCount) * 100}%` }"
                            />
                        </div>
                        <span class="w-8 text-xs text-right">{{ bucket.count }}</span>
                    </div>
                </div>
                <p v-if="analytics.scroll_heatmap.length === 0" class="text-sm text-muted-foreground">
                    No scroll data available
                </p>
            </CardContent>
        </Card>

        <!-- Section views -->
        <Card>
            <CardHeader>
                <CardTitle>Section Views</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-2">
                    <div
                        v-for="section in analytics.section_views"
                        :key="section.section"
                        class="flex items-center justify-between rounded-md border p-3"
                    >
                        <span class="text-sm font-medium">{{ section.section }}</span>
                        <Badge variant="secondary">{{ section.count }}</Badge>
                    </div>
                </div>
                <p v-if="analytics.section_views.length === 0" class="text-sm text-muted-foreground">
                    No section data available
                </p>
            </CardContent>
        </Card>

        <!-- Timeline -->
        <Card>
            <CardHeader>
                <CardTitle>View Timeline</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-2">
                    <div
                        v-for="entry in analytics.timeline"
                        :key="entry.date"
                        class="flex items-center justify-between rounded-md border p-3"
                    >
                        <span class="text-sm">{{ fmtDate(entry.date) }}</span>
                        <Badge variant="outline">{{ entry.views }} view{{ entry.views !== 1 ? 's' : '' }}</Badge>
                    </div>
                </div>
                <p v-if="analytics.timeline.length === 0" class="text-sm text-muted-foreground">
                    No timeline data available
                </p>
            </CardContent>
        </Card>
    </div>
</template>
