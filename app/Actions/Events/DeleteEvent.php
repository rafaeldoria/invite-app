<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Support\Events\EventCoverImages;

final class DeleteEvent
{
    public function __construct(private readonly EventCoverImages $covers) {}

    public function handle(Event $event): void
    {
        $cover = [
            'disk' => $event->cover_image_disk,
            'key' => $event->cover_image_key,
        ];

        $event->delete();

        $this->covers->delete($cover['disk'], $cover['key']);
    }
}
