<?php

namespace App\Notifications;

use App\Models\QuoteFollowUp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class QuoteFollowUpSentNotification extends QuoteNotification implements ShouldQueue
{
    public function __construct(
        public readonly QuoteFollowUp $followUp,
    ) {
        parent::__construct($followUp->quote);
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Follow-up sent for '.$this->quoteLabel())
            ->greeting('Follow-up delivered')
            ->line('An automated follow-up email was sent to '.$this->quote->client?->email.'.');

        if ($this->followUp->scheduled_at) {
            $message->line('Scheduled for: '.$this->followUp->scheduled_at->toDayDateTimeString());
        }

        return $message->action('Review quote', $this->quoteUrl());
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload([
            'kind' => 'quote.follow_up.sent',
            'icon' => 'mails',
            'title' => 'Follow-up sent for '.$this->quoteLabel(),
            'message' => 'Automated follow-up sent to '.$this->quote->client?->email,
            'quote_follow_up_id' => $this->followUp->id,
            'scheduled_at' => $this->followUp->scheduled_at?->toIso8601String(),
            'channel' => $this->followUp->step?->channel->value,
        ]);
    }
}
