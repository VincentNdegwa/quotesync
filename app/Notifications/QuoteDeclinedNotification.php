<?php

namespace App\Notifications;

use App\Models\Quote;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class QuoteDeclinedNotification extends QuoteNotification implements ShouldQueue
{
    public function __construct(
        Quote $quote,
        public readonly ?string $reason = null,
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
        return $this->getChannelsFromSettings($notifiable, 'notify_quote_declined', 'notify_quote_declined_channel');
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->quoteLabel().' was declined')
            ->greeting('A quote was declined')
            ->line($this->quoteLabel().' was declined by the client.');

        if (filled($this->reason)) {
            $message->line('Reason: '.$this->reason);
        }

        return $message
            ->action('View quote', $this->quoteUrl())
            ->line('Review the quote details and decide on the next step.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload([
            'kind' => 'quote.declined',
            'icon' => 'circle-x',
            'title' => $this->quoteLabel().' was declined',
            'message' => $this->reason ? 'Reason: '.$this->reason : 'The client declined the quote.',
            'reason' => $this->reason,
        ]);
    }
}
