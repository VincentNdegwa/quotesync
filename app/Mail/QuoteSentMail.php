<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteSentMail extends Mailable
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
        public string $quoteNumber,
        public string $quoteTitle,
        public string $quoteTotal,
        public ?string $validUntil,
        public ?string $coverMessage,
        /** @var array<int, array{name: string, quantity: string, total: string}> */
        public array $lineItems,
        public string $viewUrl,
        public ?string $unsubscribeUrl,
        public ?string $pdfPath = null,
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
            markdown: 'mail.quote-sent',
            with: [
                'subjectLine' => $this->subjectLine,
                'messageBody' => $this->messageBody,
                'companyName' => $this->companyName,
                'logoUrl' => $this->logoUrl,
                'quoteNumber' => $this->quoteNumber,
                'quoteTitle' => $this->quoteTitle,
                'quoteTotal' => $this->quoteTotal,
                'validUntil' => $this->validUntil,
                'coverMessage' => $this->coverMessage,
                'lineItems' => $this->lineItems,
                'viewUrl' => $this->viewUrl,
                'unsubscribeUrl' => $this->unsubscribeUrl,
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
        $attachments = [];

        if ($this->pdfPath) {
            $attachments[] = Attachment::fromStorageDisk('local', $this->pdfPath)
                ->as('quote-'.$this->quoteNumber.'.pdf')
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}
