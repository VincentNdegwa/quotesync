<script setup lang="ts">
import {
    Pencil,
    Send,
    Eye,
    CheckCircle2,
    XCircle,
    Clock,
    CalendarClock,
    Activity,
    ChevronDown,
    ChevronUp,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useEnums } from '@/composables/useEnums';
import { useFormat } from '@/composables/useFormat';
import type { GroupedActivity, QuoteActivity } from '@/types';

const props = defineProps<{
    activities: QuoteActivity[];
}>();

const { getQuoteActivityType } = useEnums();
const { formatDateTime: fmtDateTime } = useFormat();

const iconForType = (type: string) => {
    const map: Record<string, unknown> = {
        created:        Pencil,
        sent:           Send,
        viewed:         Eye,
        accepted:       CheckCircle2,
        declined:       XCircle,
        follow_up_sent: Clock,
        scheduled:      CalendarClock,
    };

    return map[type] ?? Activity;
};

const colorForType = (type: string): string => {
    return getQuoteActivityType(type)?.color ?? 'text-muted-foreground bg-muted';
};

const isMinorEvent = (type: string): boolean =>
    type === 'viewed';

const expandedGroups = ref<Set<string>>(new Set());

const toggleGroup = (id: string): void => {
    if (expandedGroups.value.has(id)) {
        expandedGroups.value.delete(id);
    } else {
        expandedGroups.value.add(id);
    }
};

const grouped = computed((): GroupedActivity[] => {
    const sorted = [...props.activities].sort((a, b) => {
        const aT = a.created_at ? Date.parse(a.created_at) : 0;
        const bT = b.created_at ? Date.parse(b.created_at) : 0;

        return bT - aT; // newest first
    });

    const result: GroupedActivity[] = [];
    let i = 0;

    while (i < sorted.length) {
        const current = sorted[i]!;

        // Collect consecutive minor events of the same type
        if (isMinorEvent(current.type)) {
            const run: QuoteActivity[] = [current];
            let j = i + 1;

            while (j < sorted.length && sorted[j]!.type === current.type) {
                run.push(sorted[j]!);
                j++;
            }

            if (run.length >= 3) {
                // Collapse into a group
                const groupId = `group-${current.id}`;
                result.push({
                    id: groupId,
                    type: current.type,
                    description: current.description,
                    created_at: current.created_at,
                    user: current.user,
                    isGroup: true,
                    groupCount: run.length,
                    groupItems: run,
                });
                i = j;
                continue;
            }
        }

        result.push(current as unknown as GroupedActivity);
        i++;
    }

    return result;
});
</script>

<template>
    <Card class="border-none shadow-sm ring-1 ring-border/50">
        <CardHeader class="pb-3">
            <div class="flex items-center justify-between">
                <CardTitle class="text-base">Activity</CardTitle>
                <span class="text-xs text-muted-foreground">
                    {{ activities.length }} event{{ activities.length !== 1 ? 's' : '' }}
                </span>
            </div>
        </CardHeader>

        <CardContent>
            <div
                v-if="grouped.length === 0"
                class="rounded-lg border border-dashed px-4 py-6 text-center text-sm text-muted-foreground"
            >
                No activity recorded yet.
            </div>

            <div
                v-else
                class="relative space-y-1 pl-0 before:absolute before:inset-y-2 before:left-3 before:w-px before:bg-border/50"
            >
                <template v-for="item in grouped" :key="item.id">

                    <template v-if="item.isGroup">
                        <div class="relative flex items-start gap-3 py-1.5">
                            <div
                                :class="[
                                    'relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full ring-2 ring-background',
                                    colorForType(item.type),
                                ]"
                            >
                                <component :is="iconForType(item.type)" class="h-3.5 w-3.5" />
                            </div>

                            <div class="flex flex-1 items-start justify-between gap-2 pt-1">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-foreground leading-tight">
                                        Quote opened
                                        <span class="font-bold text-secondary">
                                            {{ item.groupCount }}×
                                        </span>
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-muted-foreground">
                                        Most recent {{ fmtDateTime(item.created_at) }}
                                    </p>
                                </div>

                                <!-- Expand/collapse -->
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-6 shrink-0 gap-1 px-2 text-[11px] text-muted-foreground"
                                    @click="toggleGroup(item.id)"
                                >
                                    <component
                                        :is="expandedGroups.has(item.id) ? ChevronUp : ChevronDown"
                                        class="h-3 w-3"
                                    />
                                    {{ expandedGroups.has(item.id) ? 'Less' : 'All' }}
                                </Button>
                            </div>
                        </div>

                        <div
                            v-if="expandedGroups.has(item.id)"
                            class="ml-10 space-y-1 rounded-lg border bg-muted/20 px-3 py-2"
                        >
                            <div
                                v-for="(viewEvt, vi) in item.groupItems"
                                :key="`expanded-${viewEvt.id}`"
                                class="flex items-center justify-between py-1 text-xs"
                                :class="vi !== (item.groupItems?.length ?? 0) - 1 ? 'border-b border-border/30' : ''"
                            >
                                <span class="text-muted-foreground">
                                    View #{{ (item.groupItems?.length ?? 0) - vi }}
                                </span>
                                <span class="tabular-nums text-muted-foreground">
                                    {{ fmtDateTime(viewEvt.created_at) }}
                                </span>
                            </div>
                        </div>
                    </template>

                    <div
                        v-else
                        class="relative flex items-start gap-3 py-1.5"
                    >
                        <div
                            :class="[
                                'relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full ring-2 ring-background',
                                colorForType(item.type),
                            ]"
                        >
                            <component :is="iconForType(item.type)" class="h-3.5 w-3.5" />
                        </div>

                        <div class="flex-1 pt-1 min-w-0">
                            <p class="text-sm font-medium text-foreground leading-tight">
                                {{ item.description }}
                            </p>
                            <p class="mt-0.5 text-[11px] text-muted-foreground">
                                {{ fmtDateTime(item.created_at) }}
                                <span v-if="item.user" class="text-foreground/60">
                                    · {{ item.user.name }}
                                </span>
                            </p>
                        </div>
                    </div>

                </template>
            </div>
        </CardContent>
    </Card>
</template>