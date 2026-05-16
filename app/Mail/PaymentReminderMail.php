<?php

namespace App\Mail;

use App\Models\InvoiceReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public InvoiceReminder $reminder
    ) {}

    public function envelope(): Envelope
    {
        $invoice = $this->reminder->invoice;

        return new Envelope(
            subject: "Payment Reminder: Invoice #{$invoice->number}",
        );
    }

    public function content(): Content
    {
        $invoice = $this->reminder->invoice;
        $client = $invoice->client;
        $workspace = $this->reminder->workspace;

        return new Content(
            view: 'emails.payment-reminder',
            with: [
                'invoice' => $invoice,
                'client' => $client,
                'workspace' => $workspace,
                'reminderType' => $this->reminder->reminder_type,
                'dueDate' => $invoice->due_date,
                'amount' => $invoice->total,
                'currency' => $invoice->currency,
            ],
        );
    }
}
