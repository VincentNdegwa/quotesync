<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    activities: Array<{
        id: number;
        type: string;
        description: string;
        created_at: string | null;
        user: { id: number; name: string } | null;
    }>;
}>();

const iconForType = (type: string): string => {
    switch (type) {
        case 'created':
            return '✎';
        case 'sent':
            return '✈';
        case 'viewed':
            return '◉';
        case 'accepted':
            return '✓';
        case 'declined':
            return '✕';
        case 'follow_up_sent':
            return '⏰';
        case 'scheduled':
            return '🕒';
        default:
            return '•';
    }
};

const ordered = computed(() => [...props.activities].sort((a, b) => {
    const aTime = a.created_at ? Date.parse(a.created_at) : 0;
    const bTime = b.created_at ? Date.parse(b.created_at) : 0;

    return bTime - aTime;
}));
</script>

<template>
    <section class="rounded-lg border p-4">
        <h3 class="text-sm font-semibold">Activity timeline</h3>

        <ul class="mt-3 space-y-2">
            <li v-for="activity in ordered" :key="activity.id" class="rounded border px-3 py-2">
                <div class="flex items-start gap-2">
                    <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded bg-muted text-[11px]">{{ iconForType(activity.type) }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium">{{ activity.description }}</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ activity.created_at || '—' }}
                            <span v-if="activity.user"> • {{ activity.user.name }}</span>
                        </p>
                    </div>
                </div>
            </li>

            <li v-if="ordered.length === 0" class="rounded border border-dashed px-3 py-2 text-xs text-muted-foreground">
                No activity recorded yet.
            </li>
        </ul>
    </section>
</template>
