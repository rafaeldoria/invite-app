<?php

namespace App\Http\Controllers;

use App\Enums\GuestStatus;
use App\Http\Requests\Guests\StoreGuestRequest;
use App\Http\Requests\Guests\UpdateGuestRequest;
use App\Models\Event;
use App\Models\Guest;
use App\Support\Guests\GuestPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GuestController extends Controller
{
    public function __construct(
        private readonly GuestPresenter $guests,
    ) {}

    public function index(Request $request, Event $event): Response|RedirectResponse
    {
        Gate::authorize('view', $event);

        $view = $this->viewFilter($request);
        $status = $this->statusFilter($request);
        $status = $view === 'full' ? null : $status;

        $guests = $event->guests()
            ->with('companions')
            ->when($status !== null, fn ($query) => $query->where('status', $status->value))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        if ($guests->isEmpty() && $guests->total() > 0 && $guests->currentPage() > 1) {
            return redirect()->route('events.guests.index', [
                'event' => $event,
                ...array_filter([
                    'status' => $status?->value,
                    'page' => $guests->lastPage(),
                ]),
            ]);
        }

        $guests->through(fn (Guest $guest): array => $this->guests->row($event, $guest));

        $fullGuestList = $event->guests()
            ->with('companions')
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->flatMap(fn (Guest $guest) => [
                [
                    'name' => $guest->name,
                    'primary_guest' => $guest->name,
                    'is_child' => false,
                    'is_primary' => true,
                ],
                ...$guest->companions->map(fn ($companion): array => [
                    'name' => $companion->name,
                    'primary_guest' => $guest->name,
                    'is_child' => $companion->is_child,
                    'is_primary' => false,
                ])->all(),
            ])
            ->values();

        return Inertia::render('Guests/Index', [
            'event' => [
                'name' => $event->name,
                'links' => [
                    'show' => route('events.show', $event),
                    'guests' => route('events.guests.index', $event),
                ],
            ],
            'guests' => $guests,
            'fullGuestList' => $fullGuestList,
            'filters' => [
                'status' => $status?->value,
                'view' => $view,
            ],
            'statusOptions' => $this->guests->statusOptions(),
            'links' => [
                'store' => route('events.guests.store', $event),
            ],
        ]);
    }

    public function store(StoreGuestRequest $request, Event $event): RedirectResponse
    {
        $event->guests()->create($request->guestAttributes());

        return redirect()
            ->route('events.guests.index', $event)
            ->with('success', __('guests.messages.created'));
    }

    public function update(UpdateGuestRequest $request, Event $event, Guest $guest): RedirectResponse
    {
        $this->ensureGuestBelongsToEvent($event, $guest);
        $originalAdultCompanions = $guest->adult_companions;
        $originalChildCompanions = $guest->child_companions;

        $guest->update($request->guestAttributes());

        $companionCountsChanged = $guest->adult_companions !== $originalAdultCompanions
            || $guest->child_companions !== $originalChildCompanions;

        if (! $guest->status->allowsCompanions() || $companionCountsChanged) {
            $guest->companions()->delete();
        }

        return back()->with('success', __('guests.messages.updated'));
    }

    public function destroy(Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('update', $event);
        $this->ensureGuestBelongsToEvent($event, $guest);

        $guest->delete();

        return back()->with('success', __('guests.messages.deleted'));
    }

    private function statusFilter(Request $request): ?GuestStatus
    {
        $value = $request->query('status');

        if ($value === null || $value === '') {
            return null;
        }

        abort_unless(is_string($value), 404);

        $status = GuestStatus::tryFrom($value);
        abort_unless($status !== null, 404);

        return $status;
    }

    private function viewFilter(Request $request): ?string
    {
        $value = $request->query('view');

        if ($value === null || $value === '') {
            return null;
        }

        abort_unless($value === 'full', 404);

        return $value;
    }

    private function ensureGuestBelongsToEvent(Event $event, Guest $guest): void
    {
        abort_unless($guest->event_id === $event->id, 404);
    }
}
