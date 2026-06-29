<?php

namespace App\Support\Events;

use App\Models\Event;
use Illuminate\Support\Str;

final class EventPresenter
{
    public function __construct(
        private readonly EventCoverImages $covers,
        private readonly EventPublicUrls $urls,
        private readonly EventShareMessages $shareMessages,
    ) {}

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
                'public' => $this->urls->canonical($event),
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
            'share' => $this->share($event),
            'links' => [
                'index' => route('events.index'),
                'edit' => route('events.edit', $event),
                'update' => route('events.update', $event),
                'destroy' => route('events.destroy', $event),
                'public' => $this->urls->canonical($event),
                'guests' => route('events.guests.index', $event),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function publicDetail(Event $event, ?string $rsvpUrl = null): array
    {
        return [
            'name' => $event->name,
            'description' => $event->description,
            'starts_at' => $event->starts_at->toJSON(),
            'timezone' => $event->timezone,
            'location' => $event->location,
            'theme' => $event->theme,
            'cover_image' => $this->publicCoverImage($event),
            'canonical_url' => $this->urls->canonical($event),
            'rsvp' => [
                'available' => $rsvpUrl !== null,
                'url' => $rsvpUrl,
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, url: string, image: string|null}
     */
    public function publicMeta(Event $event): array
    {
        return [
            'title' => $this->plainText($event->name, 70),
            'description' => $this->plainText($event->description, 160),
            'url' => $this->urls->canonical($event),
            'image' => $this->covers->url($event),
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

    /**
     * @return array{url: string|null, width: int|null, height: int|null}|null
     */
    private function publicCoverImage(Event $event): ?array
    {
        if ($event->cover_image_key === null) {
            return null;
        }

        return [
            'url' => $this->covers->url($event),
            'width' => $event->cover_image_width,
            'height' => $event->cover_image_height,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function share(Event $event): array
    {
        $canonicalUrl = $this->urls->canonical($event);
        $locale = app()->getLocale();

        return [
            'custom_message' => $event->share_message,
            'default_message' => $this->shareMessages->default($event, $locale),
            'summary' => $this->shareMessages->summary($event, $locale),
            'final_message' => $this->shareMessages->final($event, $locale, $canonicalUrl),
            'canonical_url' => $canonicalUrl,
            'whatsapp_url' => 'https://wa.me/?text='.rawurlencode($this->shareMessages->final($event, $locale, $canonicalUrl)),
            'update_url' => route('events.share-message.update', $event),
        ];
    }

    private function plainText(?string $value, int $limit): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)) ?? '');

        return Str::limit($text, $limit, '');
    }
}
