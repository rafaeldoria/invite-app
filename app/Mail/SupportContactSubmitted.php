<?php

namespace App\Mail;

use App\Models\SupportContact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportContactSubmitted extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly SupportContact $supportContact) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Invite App support request: '.$this->supportContact->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.support.contact-submitted',
        );
    }
}
