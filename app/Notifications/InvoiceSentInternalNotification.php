<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceSentInternalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Invoice $invoice,
        public bool $scheduled,
        public ?\DateTime $scheduledAt,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Invoice Sent: '.$this->invoice->number)
            ->line('Invoice #'.$this->invoice->number.' has been sent to '.$this->invoice->client?->company_name)
            ->line('Total: '.number_format((float) $this->invoice->total, 2).' '.$this->invoice->currency)
            ->action('View Invoice', url('/invoices/'.$this->invoice->id))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->number,
            'client_name' => $this->invoice->client?->company_name,
            'total' => $this->invoice->total,
            'currency' => $this->invoice->currency,
            'scheduled' => $this->scheduled,
            'scheduled_at' => $this->scheduledAt?->format('Y-m-d H:i:s'),
        ];
    }
}
