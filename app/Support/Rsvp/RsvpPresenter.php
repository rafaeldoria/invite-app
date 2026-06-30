<?php

namespace App\Support\Rsvp;

use App\Enums\GuestStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Support\Events\EventPresenter;

final class RsvpPresenter
{
    public function __construct(
        private readonly EventPresenter $events,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function form(
        Event $event,
        string $mode,
        string $submitUrl,
        string $method,
        ?Guest $guest = null,
        ?string $responseToken = null,
        ?string $updateUrl = null,
        ?string $eventUrl = null,
    ): array {
        $guest?->loadMissing('companions');

        return [
            'event' => $this->events->publicDetail($event),
            'rsvp' => [
                'mode' => $mode,
                'submit_url' => $submitUrl,
                'method' => $method,
                'event_url' => $eventUrl,
                'response_token' => $responseToken,
                'guest_name' => $guest?->name,
                'name_locked' => $guest !== null,
                'initial' => [
                    'name' => $guest?->name ?? '',
                    'attendance' => in_array($guest?->status, [GuestStatus::Confirmed, GuestStatus::Declined], true) ? $guest->status->value : '',
                    'adult_companions' => $guest?->adult_companions ?? 0,
                    'child_companions' => $guest?->child_companions ?? 0,
                    'companions' => $this->companions($guest),
                ],
                'receipt' => $guest?->responded_at === null ? null : $this->receipt($event, $guest, $updateUrl ?? $submitUrl),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function receipt(Event $event, Guest $guest, string $updateUrl): array
    {
        return [
            'event' => [
                'name' => $event->name,
                'starts_at' => $event->starts_at->toJSON(),
                'timezone' => $event->timezone,
            ],
            'name' => $guest->name,
            'status' => $guest->status->value,
            'adult_companions' => $guest->adult_companions,
            'child_companions' => $guest->child_companions,
            'companions' => $guest->companions->map(fn ($companion): array => [
                'name' => $companion->name,
                'is_child' => $companion->is_child,
            ])->values()->all(),
            'companion_count' => $guest->companionCount(),
            'party_size' => $guest->status->allowsCompanions() ? 1 + $guest->companionCount() : 0,
            'updated_at' => $guest->responded_at?->toJSON(),
            'update_url' => $updateUrl,
        ];
    }

    /**
     * @return list<array{name: string, is_child: bool}>
     */
    private function companions(?Guest $guest): array
    {
        if ($guest === null || ! $guest->status->allowsCompanions()) {
            return [];
        }

        $companions = $guest->companions->map(fn ($companion): array => [
            'name' => $companion->name,
            'is_child' => $companion->is_child,
        ])->values()->all();

        if ($companions !== []) {
            return $companions;
        }

        return [
            ...array_fill(0, $guest->adult_companions, ['name' => '', 'is_child' => false]),
            ...array_fill(0, $guest->child_companions, ['name' => '', 'is_child' => true]),
        ];
    }
}
