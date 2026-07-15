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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GuestController extends Controller
{
    private const PER_PAGE = 10;

    public function __construct(
        private readonly GuestPresenter $guests,
    ) {}

    public function index(Request $request, Event $event): Response|RedirectResponse
    {
        Gate::authorize('view', $event);

        $view = $this->viewFilter($request);
        $status = $this->statusFilter($request);
        $fullListSort = $this->fullListSortFilter($request);
        $status = $view === 'full' ? null : $status;

        $guestQuery = $event->guests()
            ->with('companions')
            ->when($status !== null, fn ($query) => $query->where('status', $status->value))
            ->orderBy('name')
            ->orderBy('id');

        $guests = (clone $guestQuery)
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        if ($view !== 'full' && $guests->isEmpty() && $guests->total() > 0 && $guests->currentPage() > 1) {
            return redirect()->route('events.guests.index', [
                'event' => $event,
                ...array_filter([
                    'status' => $status?->value,
                    'view' => $view,
                    'page' => $guests->lastPage(),
                ]),
            ]);
        }

        $fullGuestList = $view === 'full'
            ? $this->paginatedFullGuestList($request, $event, $this->guests->fullList($guestQuery->get()), $fullListSort)
            : $this->emptyFullGuestListPaginator($request, $event);

        if ($view === 'full' && $fullGuestList->isEmpty() && $fullGuestList->total() > 0 && $fullGuestList->currentPage() > 1) {
            return redirect()->route('events.guests.index', [
                'event' => $event,
                ...array_filter([
                    'view' => $view,
                    'sort' => $fullListSort === 'guest' ? null : $fullListSort,
                    'page' => $fullGuestList->lastPage(),
                ]),
            ]);
        }

        $guests->through(fn (Guest $guest): array => $this->guests->row($event, $guest));

        return Inertia::render('Guests/Index', [
            'event' => [
                'name' => $event->name,
                'timezone' => $event->timezone,
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
                'fullListSort' => $fullListSort,
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

    private function fullListSortFilter(Request $request): string
    {
        $value = $request->query('sort');

        if ($value === null || $value === '') {
            return 'guest';
        }

        abort_unless(is_string($value), 404);
        abort_unless(in_array($value, ['guest', 'alphabetical', 'child'], true), 404);

        return $value;
    }

    /**
     * @param  Collection<int, array{name: string|null, primary_guest: string, is_child: bool, is_primary: bool, is_named: bool}>  $items
     * @return LengthAwarePaginator<int, array{name: string|null, primary_guest: string, is_child: bool, is_primary: bool, is_named: bool}>
     */
    private function paginatedFullGuestList(Request $request, Event $event, Collection $items, string $sort): LengthAwarePaginator
    {
        $sortedItems = $this->sortFullGuestList($items, $sort);
        $page = $this->currentPage($request);

        return new LengthAwarePaginator(
            $sortedItems->forPage($page, self::PER_PAGE)->values(),
            $sortedItems->count(),
            self::PER_PAGE,
            $page,
            [
                'path' => route('events.guests.index', $event),
                'query' => array_filter([
                    'view' => 'full',
                    'sort' => $sort === 'guest' ? null : $sort,
                ]),
            ],
        );
    }

    /**
     * @return LengthAwarePaginator<int, array{name: string|null, primary_guest: string, is_child: bool, is_primary: bool, is_named: bool}>
     */
    private function emptyFullGuestListPaginator(Request $request, Event $event): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, self::PER_PAGE, $this->currentPage($request), [
            'path' => route('events.guests.index', $event),
            'query' => ['view' => 'full'],
        ]);
    }

    /**
     * @param  Collection<int, array{name: string|null, primary_guest: string, is_child: bool, is_primary: bool, is_named: bool}>  $items
     * @return Collection<int, array{name: string|null, primary_guest: string, is_child: bool, is_primary: bool, is_named: bool}>
     */
    private function sortFullGuestList(Collection $items, string $sort): Collection
    {
        return $items
            ->sort(function (array $first, array $second) use ($sort): int {
                if ($sort === 'child' && $first['is_child'] !== $second['is_child']) {
                    return $first['is_child'] ? -1 : 1;
                }

                if ($sort === 'guest') {
                    $guestCompare = strnatcasecmp($first['primary_guest'], $second['primary_guest']);

                    if ($guestCompare !== 0) {
                        return $guestCompare;
                    }

                    if ($first['is_primary'] !== $second['is_primary']) {
                        return $first['is_primary'] ? -1 : 1;
                    }

                    return 0;
                }

                return strnatcasecmp($this->sortableFullListName($first), $this->sortableFullListName($second));
            })
            ->values();
    }

    /**
     * @param  array{name: string|null, primary_guest: string, is_child: bool, is_primary: bool, is_named: bool}  $item
     */
    private function sortableFullListName(array $item): string
    {
        return $item['name'] ?? $item['primary_guest'].' '.($item['is_child'] ? 'child' : 'adult');
    }

    private function currentPage(Request $request): int
    {
        $page = $request->query('page', 1);

        return is_numeric($page) ? max(1, (int) $page) : 1;
    }

    private function ensureGuestBelongsToEvent(Event $event, Guest $guest): void
    {
        abort_unless($guest->event_id === $event->id, 404);
    }
}
