<?php

namespace App\Actions\Support;

use App\Mail\SupportContactSubmitted;
use App\Models\SupportContact;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class SubmitSupportContact
{
    /**
     * @param  array{name: string, contact: string, subject: string, message: string}  $attributes
     */
    public function handle(array $attributes): SupportContact
    {
        $contact = SupportContact::query()->create($attributes);
        $recipient = config('support.email');

        if (! is_string($recipient) || trim($recipient) === '') {
            Log::warning('support.contact.email_not_configured', [
                'support_contact_id' => $contact->id,
            ]);

            return $contact;
        }

        try {
            Mail::to($recipient)->send(new SupportContactSubmitted($contact));

            $contact->forceFill([
                'email_sent_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            Log::error('support.contact.email_failed', [
                'support_contact_id' => $contact->id,
                'exception' => $exception::class,
            ]);
        }

        return $contact->refresh();
    }
}
