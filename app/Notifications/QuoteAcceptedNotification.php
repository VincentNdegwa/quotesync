<?php

namespace App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class QuoteAcceptedNotification extends QuoteNotification implements ShouldQueue
{
    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->getChannelsFromSettings($notifiable, 'notify_quote_accepted', 'notify_quote_accepted_channel');
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->quoteLabel().' was accepted')
            ->greeting('Good news')
            ->line($this->quoteLabel().' has been accepted by the client.')
            ->action('View quote', $this->quoteUrl())
            ->line('You can review the accepted quote from your dashboard.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload([
            'kind' => 'quote.accepted',
            'icon' => 'circle-check-big',
            'title' => $this->quoteLabel().' was accepted',
            'message' => 'The client accepted the quote.',
        ]);
    }
}
