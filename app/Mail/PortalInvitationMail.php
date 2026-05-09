<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PortalInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invitation;

    public $magicLink;

    public $isMagicLink;

    /**
     * Create a new message instance.
     */
    public function __construct($invitationOrMagicLink, bool $isMagicLink = false)
    {
        if ($isMagicLink) {
            $this->magicLink = $invitationOrMagicLink;
            $this->isMagicLink = true;
        } else {
            $this->invitation = $invitationOrMagicLink;
            $this->isMagicLink = false;
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isMagicLink ? 'Your Portal Login Link' : 'Portal Invitation',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.portal-invitation',
            with: [
                'invitation' => $this->invitation,
                'magicLink' => $this->magicLink,
                'isMagicLink' => $this->isMagicLink,
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
