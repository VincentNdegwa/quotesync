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
    MessageSquare,
    Trash2,
    MoreHorizontal,
    Shield,
    ShieldCheck,
    BadgeCheck,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import TiptapEditor from '@/components/ui/tiptap-editor/TiptapEditor.vue';
import { useEnums } from '@/composables/useEnums';
import { useFormat } from '@/composables/useFormat';
import type { QuoteActivity } from '@/types';
import type { CommentModel } from '@/types/models';

const editorRef = ref<InstanceType<typeof TiptapEditor> | null>(null);

const props = defineProps<{
    activities: QuoteActivity[];
    comments: CommentModel[];
    commentableId: number;
    commentableType: 'quote' | 'invoice';
    teamMembers?: Array<{ id: number; name: string }>;
}>();

const emit = defineEmits<{
    commentCreated: [];
    commentDeleted: [];
}>();

const { getQuoteActivityType } = useEnums();
const { formatDateTime: fmtDateTime } = useFormat();
const newComment = ref('');
const showAllActivity = ref(false);

const mentions = computed(() => {
    return (props.teamMembers || []).map(member => ({
        id: member.id.toString(),
        label: member.name,
    }));
});

const iconForType = (type: string) => {
    const map: Record<string, unknown> = {
        created: Pencil,
        sent: Send,
        viewed: Eye,
        accepted: CheckCircle2,
        declined: XCircle,
        follow_up_sent: Clock,
        scheduled: CalendarClock,
        approval_requested: Shield,
        approval_approved: ShieldCheck,
        approval_rejected: XCircle,
        approval_granted: BadgeCheck,
        comment: MessageSquare,
    };

    return map[type] ?? Activity;
};

const colorForType = (type: string): string => {
    const typeConfig = getQuoteActivityType(type);

    if (typeConfig?.color) {
        return typeConfig.color;
    }

    return 'text-muted-foreground bg-muted';
};


const timeline = computed(() => {
    const items: Array<{
        id: string;
        type: 'activity' | 'comment';
        timestamp: string;
        data: QuoteActivity | CommentModel;
    }> = [];

    // Add activities
    props.activities.forEach(activity => {
        items.push({
            id: `activity-${activity.id}`,
            type: 'activity',
            timestamp: activity.created_at || '',
            data: activity,
        });
    });

    // Add comments
    props.comments.forEach(comment => {
        items.push({
            id: `comment-${comment.id}`,
            type: 'comment',
            timestamp: comment.created_at || '',
            data: comment,
        });
    });

    // Sort by timestamp (oldest first - newest at bottom)
    return items.sort((a, b) => {
        const aT = a.timestamp ? Date.parse(a.timestamp) : 0;
        const bT = b.timestamp ? Date.parse(b.timestamp) : 0;

        return aT - bT;
    });
});

const displayTimeline = computed(() => {
    if (showAllActivity.value) {
        return timeline.value;
    }

    // Show only recent items (last 10 - newest items)
    return timeline.value.slice(-10);
});

const submitComment = async () => {
    if (editorRef.value?.isEmpty) {
return;
}

    if (!newComment.value.trim()) {
return;
}

    try {
        const response = await fetch(`/comments/${props.commentableType}/${props.commentableId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                content: newComment.value,
                mentions: [],
                is_internal: true,
            }),
        });

        if (response.ok) {
            newComment.value = '';
            emit('commentCreated');
        }
    } catch (error) {
        console.error('Failed to submit comment:', error);
    }
};

const deleteComment = async (commentId: number) => {
    if (!confirm('Are you sure you want to delete this comment?')) {
return;
}

    try {
        const response = await fetch(`/comments/${commentId}`, {
            method: 'DELETE',
            headers: {
            },
        });

        if (response.ok) {
            emit('commentDeleted');
        }
    } catch (error) {
        console.error('Failed to delete comment:', error);
    }
};

const handleKeyDown = (event: KeyboardEvent) => {
    if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
        event.preventDefault();
        submitComment();
    }
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-foreground">Activity & Comments</h3>
            <Badge variant="secondary" class="text-xs">
                {{ timeline.length }} item{{ timeline.length !== 1 ? 's' : '' }}
            </Badge>
        </div>

        <div class="relative space-y-3">
            <div class="absolute left-3 top-0 bottom-0 w-px bg-border/50" />

            <div
                v-for="item in displayTimeline"
                :key="item.id"
                class="relative flex items-start gap-3"
            >
                <!-- Icon -->
                <div
                    :class="[
                        'relative z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full ring-4 ring-background',
                        item.type === 'comment' ? 'bg-primary text-white' : colorForType((item.data as any).type),
                    ]"
                >
                    <component
                        :is="item.type === 'comment' ? MessageSquare : iconForType((item.data as any).type)"
                        class="h-3 w-3"
                    />
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-foreground">
                                {{ item.type === 'comment' ? (item.data as CommentModel).user?.name : (item.data as QuoteActivity).user?.name || 'System' }}
                            </span>
                            <span class="text-xs text-muted-foreground">{{ fmtDateTime(item.timestamp) }}</span>
                        </div>
                        <Button
                            v-if="item.type === 'comment'"
                            size="sm"
                            variant="ghost"
                            class="h-6 w-6 p-0 shrink-0 opacity-0 hover:opacity-100 transition-opacity"
                            @click="deleteComment((item.data as CommentModel).id)"
                        >
                            <Trash2 class="h-3 w-3" />
                        </Button>
                    </div>
                    <div class="text-sm text-foreground mt-0.5" v-html="item.type === 'comment' ? (item.data as CommentModel).content : (item.data as QuoteActivity).description"></div>
                </div>
            </div>

            <div v-if="!showAllActivity && timeline.length > 10" class="relative flex gap-3">
                <div class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full ring-4 ring-background bg-muted text-muted-foreground">
                    <MoreHorizontal class="h-4 w-4" />
                </div>
                <div class="flex-1 min-w-0 pt-1">
                    <Button variant="ghost" size="sm" @click="showAllActivity = true" class="text-xs">
                        Show {{ timeline.length - 10 }} more items
                    </Button>
                </div>
            </div>

            <div v-if="timeline.length === 0" class="text-center py-8 text-sm text-muted-foreground">
                No activity yet
            </div>
        </div>

        <div class="bg-card shadow-sm p-2 rounded-lg">
            <TiptapEditor
                ref="editorRef"
                v-model="newComment"
                placeholder="Add a comment... (Type @ to mention team members)"
                :editable="true"
                :show-toolbar="false"
                :mentions="mentions"
                class="min-h-[60px] border-0 bg-card"
                @keydown="handleKeyDown"
            />
            <div class="flex mb-3 justify-end">
                <Button size="sm" @click="submitComment" :disabled="!newComment.trim()">
                    <Send class="h-4 w-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
