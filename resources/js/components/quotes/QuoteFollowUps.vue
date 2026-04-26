<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Drawer,
    DrawerClose,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
    DrawerTrigger,
} from '@/components/ui/drawer';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { useFormat } from '@/composables/useFormat';
import { Calendar, Clock, Send, X, List } from 'lucide-vue-next';

const props = defineProps<{
    quoteId: number;
    followUps: Array<{
        id: number;
        scheduled_at: string;
        status: string;
        sent_at: string | null;
        cancelled_at: string | null;
        step: {
            id: number;
            channel: string;
            subject: string;
            day_offset: number;
        };
    }>;
}>();

const { formatDate: fmtDate } = useFormat();

const cancelDialogOpen = ref(false);
const sendNowDialogOpen = ref(false);
const selectedFollowUpId = ref<number | null>(null);
const processing = ref(false);

const pendingFollowUps = computed(() => 
    props.followUps.filter(f => f.status === 'pending')
);

const sentFollowUps = computed(() => 
    props.followUps.filter(f => f.status === 'sent')
);

const cancelledFollowUps = computed(() => 
    props.followUps.filter(f => f.status === 'cancelled')
);

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'pending':
            return { variant: 'secondary' as const, label: 'Scheduled' };
        case 'sent':
            return { variant: 'default' as const, label: 'Sent' };
        case 'cancelled':
            return { variant: 'destructive' as const, label: 'Cancelled' };
        default:
            return { variant: 'secondary' as const, label: status };
    }
};

const handleCancel = (id: number) => {
    selectedFollowUpId.value = id;
    cancelDialogOpen.value = true;
};

const handleSendNow = (id: number) => {
    selectedFollowUpId.value = id;
    sendNowDialogOpen.value = true;
};

const confirmCancel = () => {
    if (!selectedFollowUpId.value) return;
    processing.value = true;
    router.post(`/quotes/${props.quoteId}/follow-ups/${selectedFollowUpId.value}/cancel`, {}, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            cancelDialogOpen.value = false;
            selectedFollowUpId.value = null;
        },
    });
};

const confirmSendNow = () => {
    if (!selectedFollowUpId.value) return;
    processing.value = true;
    router.post(`/quotes/${props.quoteId}/follow-ups/${selectedFollowUpId.value}/send-now`, {}, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            sendNowDialogOpen.value = false;
            selectedFollowUpId.value = null;
        },
    });
};
</script>

<template>
    <Drawer direction="right">
        <DrawerTrigger as-child>
            <Button variant="outline" size="sm">
                <List class="h-4 w-4 mr-2" />
                Follow-ups ({{ followUps.length }})
            </Button>
        </DrawerTrigger>
        <DrawerContent class="w-[400px]">
            <DrawerHeader>
                <DrawerTitle class="flex items-center gap-2">
                    <Calendar class="h-5 w-5" />
                    Scheduled Follow-ups
                </DrawerTitle>
                <DrawerDescription>
                    Automated follow-ups scheduled for this quote
                </DrawerDescription>
            </DrawerHeader>
            
            <div class="flex-1 overflow-y-auto px-4 pb-4">
                <div v-if="followUps.length === 0" class="text-sm text-muted-foreground py-4">
                    No follow-ups scheduled for this quote.
                </div>
                
                <div v-else class="space-y-3">
                    <!-- Pending Follow-ups -->
                    <div v-if="pendingFollowUps.length > 0" class="space-y-2">
                        <div v-for="followUp in pendingFollowUps" :key="followUp.id" 
                            class="flex items-start justify-between gap-3 rounded-lg border p-3 bg-muted/30">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <Badge v-bind="getStatusBadge(followUp.status)">
                                        {{ getStatusBadge(followUp.status).label }}
                                    </Badge>
                                    <span class="text-xs text-muted-foreground">
                                        Day {{ followUp.step.day_offset }}
                                    </span>
                                </div>
                                <p class="text-sm font-medium truncate">{{ followUp.step.subject }}</p>
                                <div class="flex items-center gap-1 text-xs text-muted-foreground mt-1">
                                    <Clock class="h-3 w-3" />
                                    {{ fmtDate(followUp.scheduled_at) }}
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button size="sm" variant="outline" @click="handleSendNow(followUp.id)">
                                    <Send class="h-3 w-3 mr-1" />
                                    Send Now
                                </Button>
                                <Button size="sm" variant="ghost" @click="handleCancel(followUp.id)">
                                    <X class="h-3 w-3" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Sent Follow-ups -->
                    <div v-if="sentFollowUps.length > 0" class="space-y-2">
                        <div v-for="followUp in sentFollowUps" :key="followUp.id" 
                            class="flex items-start justify-between gap-3 rounded-lg border p-3 opacity-60">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <Badge v-bind="getStatusBadge(followUp.status)">
                                        {{ getStatusBadge(followUp.status).label }}
                                    </Badge>
                                    <span class="text-xs text-muted-foreground">
                                        Day {{ followUp.step.day_offset }}
                                    </span>
                                </div>
                                <p class="text-sm font-medium truncate">{{ followUp.step.subject }}</p>
                                <div class="flex items-center gap-1 text-xs text-muted-foreground mt-1">
                                    <Clock class="h-3 w-3" />
                                    Sent {{ fmtDate(followUp.sent_at) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cancelled Follow-ups -->
                    <div v-if="cancelledFollowUps.length > 0" class="space-y-2">
                        <div v-for="followUp in cancelledFollowUps" :key="followUp.id" 
                            class="flex items-start justify-between gap-3 rounded-lg border p-3 opacity-40">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <Badge v-bind="getStatusBadge(followUp.status)">
                                        {{ getStatusBadge(followUp.status).label }}
                                    </Badge>
                                    <span class="text-xs text-muted-foreground">
                                        Day {{ followUp.step.day_offset }}
                                    </span>
                                </div>
                                <p class="text-sm font-medium truncate">{{ followUp.step.subject }}</p>
                                <div class="flex items-center gap-1 text-xs text-muted-foreground mt-1">
                                    <Clock class="h-3 w-3" />
                                    Cancelled {{ fmtDate(followUp.cancelled_at) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <DrawerFooter>
                <DrawerClose as-child>
                    <Button variant="outline">Close</Button>
                </DrawerClose>
            </DrawerFooter>
        </DrawerContent>
    </Drawer>

    <ConfirmDialog
        v-model:open="cancelDialogOpen"
        title="Cancel follow-up?"
        description="This action cannot be undone. The follow-up will be cancelled and will not be sent."
        confirm-text="Cancel follow-up"
        cancel-text="Keep scheduled"
        variant="destructive"
        :processing="processing"
        @confirm="confirmCancel"
    />

    <ConfirmDialog
        v-model:open="sendNowDialogOpen"
        title="Send follow-up now?"
        description="This will send the follow-up email immediately instead of waiting for the scheduled time."
        confirm-text="Send now"
        cancel-text="Cancel"
        variant="default"
        :processing="processing"
        @confirm="confirmSendNow"
    />
</template>
