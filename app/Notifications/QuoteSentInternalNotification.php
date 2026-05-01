<?php

namespace App\Notifications;

use App\Models\Quote;

class QuoteSentInternalNotification extends QuoteNotification
{
    public function __construct(
        Quote $quote,
        public readonly bool $scheduled,
        public readonly ?string $scheduledAt,
    ) {
        parent::__construct($quote);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->getChannelsFromSettings($notifiable, 'notify_quote_sent', 'notify_quote_sent_channel');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $subject = sprintf('%s was %s', $this->quoteLabel(), $this->scheduled ? 'scheduled' : 'sent');

        $message = $this->scheduled && $this->scheduledAt
            ? 'Delivery is scheduled for '.$this->scheduledAt.'.'
            : 'Delivery has started for the client.';

        return $this->payload([
            'kind' => 'quote.sent',
            'icon' => 'send',
            'title' => $subject,
            'message' => $message,
        ]);
    }
}
