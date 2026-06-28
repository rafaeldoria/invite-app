<?php

namespace App\Support\Events;

use App\Models\Event;
use App\Models\Guest;

final class EventPublicUrls
{
    public function canonical(Event $event): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        return $baseUrl.route('public.events.show', $event, false);
    }

    public function invitation(Event $event, Guest $guest): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        return $baseUrl.route('public.invitations.show', [
            'event' => $event,
            'token' => $guest->invitation_token,
        ], false);
    }
}
