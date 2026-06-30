<?php

namespace App\Support\Guests;

use App\Enums\GuestStatus;
use App\Models\Event;
use App\Models\Guest;

final class GuestPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function row(Event $event, Guest $guest): array
    {
        return [
            'name' => $guest->name,
            'status' => $guest->status->value,
            'adult_companions' => $guest->adult_companions,
            'child_companions' => $guest->child_companions,
            'companion_count' => $guest->companionCount(),
            'companions' => $guest->companions->map(fn ($companion): array => [
                'name' => $companion->name,
                'is_child' => $companion->is_child,
            ])->values()->all(),
            'links' => [
                'update' => route('events.guests.update', [$event, $guest]),
                'destroy' => route('events.guests.destroy', [$event, $guest]),
            ],
        ];
    }

    /**
     * @return array<int, array{value: string, label_key: string}>
     */
    public function statusOptions(): array
    {
        return array_map(fn (GuestStatus $status): array => [
            'value' => $status->value,
            'label_key' => 'guests.status.'.$status->value,
        ], GuestStatus::cases());
    }
}
