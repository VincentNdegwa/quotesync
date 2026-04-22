<?php

namespace App\Notifications;

class QuoteExpiredNotification extends QuoteNotification
{
    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload([
            'kind' => 'quote.expired',
            'icon' => 'clock-3',
            'title' => $this->quoteLabel().' expired',
            'message' => $this->quote->valid_until
                ? 'Expired on '.$this->quote->valid_until->toFormattedDateString().'.'
                : 'The quote expired.',
        ]);
    }
}
