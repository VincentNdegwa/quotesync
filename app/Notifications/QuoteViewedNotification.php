<?php

namespace App\Notifications;

class QuoteViewedNotification extends QuoteNotification
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
            'kind' => 'quote.viewed',
            'icon' => 'eye',
            'title' => $this->quoteLabel().' was viewed',
            'message' => 'The client opened the quote.',
        ]);
    }
}
