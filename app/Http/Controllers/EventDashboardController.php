<?php

namespace App\Http\Controllers;

use App\Enums\GuestStatus;
use App\Models\Event;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EventDashboardController extends Controller
{
    public function __invoke(Event $event): Response
    {
        Gate::authorize('view', $event);

        $summary = $event->guests()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as confirmed', [GuestStatus::Confirmed->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as declined', [GuestStatus::Declined->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending', [GuestStatus::Pending->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 + adult_companions + child_companions ELSE 0 END) as expected_attendees', [GuestStatus::Confirmed->value])
            ->first();

        return Inertia::render('Events/Dashboard', [
            'event' => [
                'name' => $event->name,
                'links' => [
                    'show' => route('events.show', $event),
                    'guests' => route('events.guests.index', $event),
                ],
            ],
            'metrics' => [
                'total' => (int) ($summary->total ?? 0),
                'confirmed' => (int) ($summary->confirmed ?? 0),
                'declined' => (int) ($summary->declined ?? 0),
                'pending' => (int) ($summary->pending ?? 0),
                'expected_attendees' => (int) ($summary->expected_attendees ?? 0),
            ],
            'links' => [
                'guests' => [
                    'all' => route('events.guests.index', $event),
                    GuestStatus::Confirmed->value => route('events.guests.index', [
                        'event' => $event,
                        'status' => GuestStatus::Confirmed->value,
                    ]),
                    GuestStatus::Declined->value => route('events.guests.index', [
                        'event' => $event,
                        'status' => GuestStatus::Declined->value,
                    ]),
                    GuestStatus::Pending->value => route('events.guests.index', [
                        'event' => $event,
                        'status' => GuestStatus::Pending->value,
                    ]),
                ],
            ],
        ]);
    }
}
