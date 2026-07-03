<?php

namespace Tests\Feature\Auth;

use Illuminate\Mail\Transport\ResendTransport;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailConfigurationTest extends TestCase
{
    public function test_resend_mailer_uses_laravel_resend_transport(): void
    {
        config()->set('services.resend.key', 're_test_key');
        config()->set('mail.mailers.resend', [
            'transport' => 'resend',
        ]);

        Mail::purge('resend');

        $transport = Mail::mailer('resend')->getSymfonyTransport();

        $this->assertInstanceOf(ResendTransport::class, $transport);
    }
}
