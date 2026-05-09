<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteFollowUpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $messageBody,
        public string $companyName,
        public ?string $logoUrl,
        public string $viewUrl,
        public ?string $validUntil,
        public ?string $unsubscribeUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.quote-follow-up',
            with: [
                'subjectLine' => $this->subjectLine,
                'messageBody' => $this->messageBody,
                'companyName' => $this->companyName,
                'logoUrl' => $this->logoUrl,
                'viewUrl' => $this->viewUrl,
                'validUntil' => $this->validUntil,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ],
        );
    }
}
