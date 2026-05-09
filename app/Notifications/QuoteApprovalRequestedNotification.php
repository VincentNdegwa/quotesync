<?php

namespace App\Notifications;

use App\Models\QuoteApproval;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class QuoteApprovalRequestedNotification extends QuoteNotification implements ShouldQueue
{
    public function __construct(
        public readonly QuoteApproval $approval,
    ) {
        parent::__construct($approval->quote);
    }

    public function via(object $notifiable): array
    {
        return $this->getChannelsFromSettings($notifiable, 'notify_approval_requested', 'notify_approval_requested_channel');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->quoteLabel().' requires your approval')
            ->greeting('Approval requested')
            ->line('A quote that matches one of your approval rules needs your review.')
            ->line('Quote: '.$this->quoteLabel())
            ->action('Review approvals', route('approvals.index'));
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload([
            'kind' => 'quote.approval.requested',
            'icon' => 'shield-check',
            'title' => 'Approval required for '.$this->quoteLabel(),
            'message' => 'A new approval request awaits your decision.',
            'quote_approval_id' => $this->approval->id,
            'approval_rule_id' => $this->approval->approval_rule_id,
        ]);
    }
}
