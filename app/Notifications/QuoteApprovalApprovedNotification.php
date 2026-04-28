<?php

namespace App\Notifications;

use App\Models\QuoteApproval;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class QuoteApprovalApprovedNotification extends QuoteNotification implements ShouldQueue
{
    public function __construct(
        public readonly QuoteApproval $approval,
    ) {
        parent::__construct($approval->quote);
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Approval granted for '.$this->quoteLabel())
            ->greeting('Approval recorded')
            ->line('An approver has approved '.$this->quoteLabel().'.');

        if ($this->approval->comment) {
            $message->line('Comment: '.$this->approval->comment);
        }

        return $message
            ->action('Review quote', $this->quoteUrl())
            ->line('You can now continue progressing the quote.');
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload([
            'kind' => 'quote.approval.approved',
            'icon' => 'circle-check',
            'title' => 'Approval recorded for '.$this->quoteLabel(),
            'message' => $this->approval->comment
                ? 'Comment: '.$this->approval->comment
                : 'Approval recorded by '.$this->approval->approver?->name,
            'quote_approval_id' => $this->approval->id,
            'comment' => $this->approval->comment,
        ]);
    }
}
