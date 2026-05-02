<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceSentMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $subjectLine,
        public string $messageBody,
        public string $companyName,
        public ?string $logoUrl,
        public string $invoiceNumber,
        public string $invoiceTitle,
        public string $invoiceTotal,
        public ?string $dueDate,
        public ?string $notes,
        /** @var array<int, array{name: string, quantity: string, total: string}> */
        public array $lineItems,
        public ?string $publicInvoiceUrl = null,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invoice-sent',
            with: [
                'subjectLine' => $this->subjectLine,
                'messageBody' => $this->messageBody,
                'companyName' => $this->companyName,
                'logoUrl' => $this->logoUrl,
                'invoiceNumber' => $this->invoiceNumber,
                'invoiceTitle' => $this->invoiceTitle,
                'invoiceTotal' => $this->invoiceTotal,
                'dueDate' => $this->dueDate,
                'notes' => $this->notes,
                'lineItems' => $this->lineItems,
                'publicInvoiceUrl' => $this->publicInvoiceUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
