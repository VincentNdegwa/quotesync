<?php

namespace App\Notifications;

use App\Models\Quote;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class QuoteApprovalGrantedNotification extends QuoteNotification implements ShouldQueue
{
    public function __construct(
        Quote $quote,
        public readonly ?string $grantedByName,
        public readonly ?\DateTimeInterface $grantedAt,
    ) {
        parent::__construct($quote);
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('All approvals granted for '.$this->quoteLabel())
            ->greeting('Approvals complete')
            ->line('Every required approval has been granted for '.$this->quoteLabel().'.');

        if ($this->grantedByName) {
            $message->line('Last action by: '.$this->grantedByName);
        }

        return $message
            ->action('Send quote', route('quotes.show', $this->quote))
            ->line('You can now send the quote to the client.');
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload([
            'kind' => 'quote.approval.granted',
            'icon' => 'shield-check',
            'title' => 'Approvals complete for '.$this->quoteLabel(),
            'message' => $this->grantedByName
                ? 'Last approved by '.$this->grantedByName
                : 'All approvals are complete.',
            'granted_at' => $this->grantedAt?->format(DATE_ATOM),
        ]);
    }
}
