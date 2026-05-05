<script setup lang="ts">
import { ref } from 'vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Textarea } from '@/components/ui/textarea';
import type { CommentModel } from '@/types/models';

const props = defineProps<{
    comments: CommentModel[];
    commentableId: number;
    commentableType: 'quote' | 'invoice';
}>();

const emit = defineEmits<{
    created: [];
    deleted: [];
}>();

const newComment = ref('');
const mentionQuery = ref('');
const mentionedUsers = ref<number[]>([]);

const submitComment = async () => {
    if (!newComment.value.trim()) {
return;
}

    try {
        const response = await fetch(`/comments/${props.commentableType}/${props.commentableId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                content: newComment.value,
                mentions: [],
                is_internal: true,
            }),
        });

        if (response.ok) {
            newComment.value = '';
            emit('created');
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
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (response.ok) {
            emit('deleted');
        }
    } catch (error) {
        console.error('Failed to delete comment:', error);
    }
};

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const formatDate = (date: string | null) => {
    if (!date) {
return '—';
}

    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const handleMention = (e: KeyboardEvent) => {
    if (e.key === '@') {
        // Trigger mention picker
    }
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-foreground">Comments</h3>
            <span class="text-xs text-muted-foreground">{{ comments.length }} comments</span>
        </div>

        <!-- New Comment Form -->
        <div class="space-y-2">
            <Textarea
                v-model="newComment"
                placeholder="Add a comment... Use @ to mention team members"
                rows="3"
                @keydown="handleMention"
            />
            <div class="flex justify-end">
                <Button size="sm" @click="submitComment" :disabled="!newComment.trim()">
                    Post Comment
                </Button>
            </div>
        </div>

        <!-- Comments List -->
        <ScrollArea class="h-64">
            <div class="space-y-3 pr-4">
                <div
                    v-for="comment in comments"
                    :key="comment.id"
                    class="group rounded-lg border bg-card p-3"
                >
                    <div class="flex items-start gap-3">
                        <Avatar class="h-8 w-8 flex-shrink-0">
                            <AvatarFallback class="text-xs">
                                {{ getInitials(comment.user?.name || 'U') }}
                            </AvatarFallback>
                        </Avatar>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-sm font-medium text-foreground">
                                    {{ comment.user?.name || 'Unknown' }}
                                </span>
                                <span class="text-xs text-muted-foreground">
                                    {{ formatDate(comment.created_at) }}
                                </span>
                                <Badge v-if="comment.is_internal" variant="secondary" class="text-xs">
                                    Internal
                                </Badge>
                            </div>
                            <p class="text-sm text-foreground whitespace-pre-wrap break-words">
                                {{ comment.content }}
                            </p>
                        </div>

                        <Button
                            size="sm"
                            variant="ghost"
                            class="h-6 w-6 p-0 opacity-0 group-hover:opacity-100 transition-opacity"
                            @click="deleteComment(comment.id)"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </Button>
                    </div>
                </div>

                <div v-if="comments.length === 0" class="text-center py-8 text-sm text-muted-foreground">
                    No comments yet. Add the first comment above.
                </div>
            </div>
        </ScrollArea>
    </div>
</template>
