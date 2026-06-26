<?php

namespace App\Support\Events;

use App\Models\Event;

final class EventPresenter
{
    public function __construct(private readonly EventCoverImages $covers) {}

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function timezoneOptions(?string $include = null): array
    {
        $values = array_values(array_unique(array_filter([
            'America/Sao_Paulo',
            'UTC',
            'America/New_York',
            'Europe/London',
            $include,
        ])));

        return array_map(fn (string $timezone): array => [
            'value' => $timezone,
            'label' => $timezone,
        ], $values);
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(Event $event): array
    {
        return [
            'public_id' => $event->public_id,
            'name' => $event->name,
            'starts_at' => $event->starts_at->toJSON(),
            'starts_date' => $event->local_starts_date,
            'starts_time' => $event->local_starts_time,
            'timezone' => $event->timezone,
            'location' => $event->location,
            'theme' => $event->theme,
            'cover_image' => $this->coverImage($event),
            'links' => [
                'show' => route('events.show', $event),
                'edit' => route('events.edit', $event),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Event $event): array
    {
        return [
            ...$this->summary($event),
            'description' => $event->description,
            'links' => [
                'index' => route('events.index'),
                'edit' => route('events.edit', $event),
                'update' => route('events.update', $event),
                'destroy' => route('events.destroy', $event),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function coverImage(Event $event): ?array
    {
        if ($event->cover_image_key === null) {
            return null;
        }

        return [
            'url' => $this->covers->url($event),
            'mime' => $event->cover_image_mime,
            'size' => $event->cover_image_size,
            'width' => $event->cover_image_width,
            'height' => $event->cover_image_height,
        ];
    }
}
