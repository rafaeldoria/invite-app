<?php

namespace App\Support\Guests;

use App\Enums\GuestStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Support\Events\EventPublicUrls;
use Illuminate\Support\Collection;

final class GuestPresenter
{
    public function __construct(
        private readonly EventPublicUrls $urls,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function row(Event $event, Guest $guest): array
    {
        return [
            'name' => $guest->name,
            'invitation_url' => $this->urls->invitation($event, $guest),
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
     * @return Collection<int, array{name: string|null, primary_guest: string, is_child: bool, is_primary: bool, is_named: bool}>
     */
    public function fullList(Event $event): Collection
    {
        return $event->guests()
            ->with('companions')
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->flatMap(fn (Guest $guest): array => [
                [
                    'name' => $guest->name,
                    'primary_guest' => $guest->name,
                    'is_child' => false,
                    'is_primary' => true,
                    'is_named' => true,
                ],
                ...$guest->companions->map(fn ($companion): array => [
                    'name' => $companion->name,
                    'primary_guest' => $guest->name,
                    'is_child' => $companion->is_child,
                    'is_primary' => false,
                    'is_named' => true,
                ])->all(),
                ...$this->countOnlyCompanions($guest),
            ])
            ->values();
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

    /**
     * @return list<array{name: null, primary_guest: string, is_child: bool, is_primary: false, is_named: false}>
     */
    private function countOnlyCompanions(Guest $guest): array
    {
        if (! $guest->status->allowsCompanions()) {
            return [];
        }

        $namedAdultCompanions = $guest->companions->where('is_child', false)->count();
        $namedChildCompanions = $guest->companions->where('is_child', true)->count();

        return [
            ...$this->placeholderCompanions($guest, false, max(0, $guest->adult_companions - $namedAdultCompanions)),
            ...$this->placeholderCompanions($guest, true, max(0, $guest->child_companions - $namedChildCompanions)),
        ];
    }

    /**
     * @return list<array{name: null, primary_guest: string, is_child: bool, is_primary: false, is_named: false}>
     */
    private function placeholderCompanions(Guest $guest, bool $isChild, int $count): array
    {
        return array_fill(0, $count, [
            'name' => null,
            'primary_guest' => $guest->name,
            'is_child' => $isChild,
            'is_primary' => false,
            'is_named' => false,
        ]);
    }
}
