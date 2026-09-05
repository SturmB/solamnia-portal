<?php

namespace App\Mail;

use App\Models\Invite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Spatie\Mjml\Mjml;

class InviteMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Invite $invite, public string $token) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're invited to Solamnia",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $mjml = view('mail.invite', [
            'acceptUrl' => route('invites.show', $this->token),
            'inviterName' => $this->invite->inviter->name,
            'expiresAt' => $this->invite->expires_at,
        ])->render();

        return new Content(htmlString: Mjml::new()->toHtml($mjml));
    }
}
