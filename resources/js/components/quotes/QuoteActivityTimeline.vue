<script setup lang="ts">
import { computed } from 'vue';
import type { QuoteActivity } from '@/types';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { 
    Pencil, 
    Send, 
    Eye, 
    CheckCircle2, 
    XCircle, 
    Clock, 
    CalendarClock,
    Activity
} from 'lucide-vue-next';

const props = defineProps<{
    activities: QuoteActivity[];
}>();

const iconForType = (type: string) => {
    switch (type) {
        case 'created':
            return Pencil;
        case 'sent':
            return Send;
        case 'viewed':
            return Eye;
        case 'accepted':
            return CheckCircle2;
        case 'declined':
            return XCircle;
        case 'follow_up_sent':
            return Clock;
        case 'scheduled':
            return CalendarClock;
        default:
            return Activity;
    }
};

const iconClassForType = (type: string): string => {
    switch (type) {
        case 'created': return 'text-blue-500 bg-blue-500/10';
        case 'sent': return 'text-indigo-500 bg-indigo-500/10';
        case 'viewed': return 'text-purple-500 bg-purple-500/10';
        case 'accepted': return 'text-emerald-500 bg-emerald-500/10';
        case 'declined': return 'text-rose-500 bg-rose-500/10';
        case 'follow_up_sent': return 'text-orange-500 bg-orange-500/10';
        case 'scheduled': return 'text-amber-500 bg-amber-500/10';
        default: return 'text-muted-foreground bg-muted';
    }
};

const ordered = computed(() => [...props.activities].sort((a, b) => {
    const aTime = a.created_at ? Date.parse(a.created_at) : 0;
    const bTime = b.created_at ? Date.parse(b.created_at) : 0;

    return bTime - aTime;
}));
</script>

<template>
    <Card class="border-none shadow-sm ring-1 ring-border/50">
        <CardHeader class="pb-3">
            <CardTitle class="text-lg">Activity Timeline</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="relative pl-3 mt-2 space-y-6 before:absolute before:inset-y-0 before:left-3.5 before:w-px before:bg-border/50">
                <div v-for="activity in ordered" :key="activity.id" class="relative flex items-start gap-4">
                    <div :class="['relative z-10 flex h-7 w-7 items-center justify-center rounded-full ring-4 ring-background', iconClassForType(activity.type)]">
                        <component :is="iconForType(activity.type)" class="h-3.5 w-3.5" />
                    </div>
                    <div class="flex-1 pt-1 min-w-0">
                        <p class="text-sm font-medium text-foreground leading-tight">{{ activity.description }}</p>
                        <p class="mt-1 text-[11px] font-medium uppercase tracking-wider text-muted-foreground">
                            {{ activity.created_at ? new Date(activity.created_at).toLocaleString() : '—' }}
                            <span v-if="activity.user" class="text-foreground/70"> • {{ activity.user.name }}</span>
                        </p>
                    </div>
                </div>

                <div v-if="ordered.length === 0" class="rounded-lg border border-dashed px-4 py-6 text-center text-sm text-muted-foreground">
                    No activity recorded yet.
                </div>
            </div>
        </CardContent>
    </Card>
</template>
