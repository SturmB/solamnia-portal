<?php

namespace App\Mail;

use App\Models\Campaign;
use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class CampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Campaign $campaign, public ?Subscriber $subscriber = null) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->campaign->subject);
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(htmlString: $this->campaign->renderHtml($this->subscriber));
    }

    /**
     * Get the message headers.
     */
    public function headers(): Headers
    {
        if ($this->subscriber === null) {
            return new Headers;
        }

        $url = URL::signedRoute('unsubscribe', ['subscriber' => $this->subscriber]);

        return new Headers(text: [
            'List-Unsubscribe' => '<'.$url.'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }
}
