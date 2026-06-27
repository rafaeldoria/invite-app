<?php

namespace App\Support\Events;

use App\Models\Event;

final class EventPublicUrls
{
    public function canonical(Event $event): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        return $baseUrl.route('public.events.show', $event, false);
    }
}
