<?php

namespace App\Mail;

use App\Models\FinancePOP;
use App\Models\DeveloperMagicLink;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class POPDeveloperNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $pop;
    public $magicLink;
    public $portalUrl;
    public $ccEmails;

    /**
     * Create a new message instance.
     */
    public function __construct(FinancePOP $pop, DeveloperMagicLink $magicLink, $ccEmails = null)
    {
        $this->pop = $pop;
        $this->magicLink = $magicLink;
        $this->portalUrl = config('app.frontend_url') . '/developer/portal?token=' . $magicLink->token;
        $this->ccEmails = $ccEmails;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $envelope = new Envelope(
            subject: 'New Payment - Proof of Payment Received - ' . $this->pop->pop_number,
        );

        // Add CC emails if provided
        if ($this->ccEmails) {
            $ccArray = array_map('trim', explode(',', $this->ccEmails));
            $ccArray = array_filter($ccArray); // Remove empty values
            if (!empty($ccArray)) {
                $envelope->cc($ccArray);
            }
        }

        return $envelope;
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.pop-developer-notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
