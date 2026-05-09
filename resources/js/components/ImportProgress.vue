<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    importHistory: {
        id: number;
        type: 'clients' | 'catalog';
        status: 'pending' | 'processing' | 'completed' | 'failed';
        total_rows: number;
        processed_rows: number;
        failed_rows: number;
        error_details?: string[];
        started_at?: string;
        completed_at?: string;
    };
}>();

const progressPercentage = computed(() => {
    if (props.importHistory.total_rows === 0) {
        return 0;
    }

    return Math.round((props.importHistory.processed_rows / props.importHistory.total_rows) * 100);
});

const statusColor = computed(() => {
    switch (props.importHistory.status) {
        case 'pending':
            return 'bg-yellow-500';
        case 'processing':
            return 'bg-blue-500';
        case 'completed':
            return 'bg-green-500';
        case 'failed':
            return 'bg-red-500';
        default:
            return 'bg-gray-500';
    }
});

const statusText = computed(() => {
    switch (props.importHistory.status) {
        case 'pending':
            return 'Pending';
        case 'processing':
            return 'Processing';
        case 'completed':
            return 'Completed';
        case 'failed':
            return 'Failed';
        default:
            return 'Unknown';
    }
});
</script>

<template>
    <div class="rounded-lg border p-4 space-y-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div :class="['w-3 h-3 rounded-full', statusColor]" />
                <span class="font-medium">{{ importHistory.type === 'clients' ? 'Client' : 'Catalog' }} Import</span>
            </div>
            <span class="text-sm text-muted-foreground">{{ statusText }}</span>
        </div>

        <div class="space-y-1">
            <div class="flex justify-between text-sm">
                <span>{{ importHistory.processed_rows }} / {{ importHistory.total_rows }} rows</span>
                <span>{{ progressPercentage }}%</span>
            </div>
            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                <div
                    :class="['h-full transition-all duration-300', statusColor]"
                    :style="{ width: `${progressPercentage}%` }"
                />
            </div>
        </div>

        <div v-if="importHistory.failed_rows > 0" class="text-sm text-destructive">
            {{ importHistory.failed_rows }} rows failed
        </div>

        <div v-if="importHistory.error_details && importHistory.error_details.length > 0" class="mt-2">
            <details class="text-sm">
                <summary class="cursor-pointer text-muted-foreground hover:text-foreground">
                    View errors ({{ importHistory.error_details.length }})
                </summary>
                <ul class="mt-2 space-y-1 pl-4 list-disc text-destructive">
                    <li v-for="(error, index) in importHistory.error_details.slice(0, 10)" :key="index">
                        {{ error }}
                    </li>
                    <li v-if="importHistory.error_details.length > 10" class="text-muted-foreground">
                        ... and {{ importHistory.error_details.length - 10 }} more
                    </li>
                </ul>
            </details>
        </div>
    </div>
</template>
