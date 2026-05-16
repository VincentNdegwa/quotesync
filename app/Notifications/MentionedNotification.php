<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MentionedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Comment $comment,
        public mixed $commentable
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $commentableType = class_basename($this->commentable);
        $commentableId = $this->commentable->id;
        $commentableTitle = match ($commentableType) {
            'Quote' => $this->commentable->title ?? $this->commentable->number,
            'Invoice' => $this->commentable->title ?? $this->commentable->invoice_number,
            default => 'Document',
        };

        return [
            'comment_id' => $this->comment->id,
            'commentable_type' => $commentableType,
            'commentable_id' => $commentableId,
            'commentable_title' => $commentableTitle,
            'content' => $this->comment->content,
            'mentioned_by' => $this->comment->user->name,
        ];
    }
}
