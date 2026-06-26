<?php

namespace App\Http\Controllers;

use App\Actions\Events\CreateEvent;
use App\Actions\Events\DeleteEvent;
use App\Actions\Events\UpdateEvent as UpdateEventAction;
use App\Http\Requests\Events\StoreEventRequest;
use App\Http\Requests\Events\UpdateEventRequest;
use App\Models\Event;
use App\Support\Events\EventPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class EventController extends Controller
{
    public function __construct(
        private readonly CreateEvent $createEvent,
        private readonly UpdateEventAction $updateEvent,
        private readonly DeleteEvent $deleteEvent,
        private readonly EventPresenter $events,
    ) {}

    public function index(Request $request): Response
    {
        $events = $request->user()
            ->events()
            ->latest('starts_at')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Event $event): array => $this->events->summary($event));

        return Inertia::render('Events/Index', [
            'events' => $events,
            'links' => [
                'create' => route('events.create'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Events/Create', [
            'defaults' => [
                'timezone' => 'America/Sao_Paulo',
            ],
            'timezoneOptions' => $this->events->timezoneOptions(),
            'links' => [
                'store' => route('events.store'),
                'index' => route('events.index'),
            ],
        ]);
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        try {
            $event = $this->createEvent->handle($request);
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->except('cover_image'))
                ->with('error', __('events.messages.save_failed'));
        }

        return redirect()
            ->route('events.show', $event)
            ->with('success', __('events.messages.created'));
    }

    public function show(Event $event): Response
    {
        Gate::authorize('view', $event);

        return Inertia::render('Events/Show', [
            'event' => $this->events->detail($event),
        ]);
    }

    public function edit(Event $event): Response
    {
        Gate::authorize('update', $event);

        return Inertia::render('Events/Edit', [
            'event' => $this->events->detail($event),
            'timezoneOptions' => $this->events->timezoneOptions($event->timezone),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        try {
            $this->updateEvent->handle($request, $event);
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->except('cover_image'))
                ->with('error', __('events.messages.save_failed'));
        }

        return redirect()
            ->route('events.show', $event)
            ->with('success', __('events.messages.updated'));
    }

    public function destroy(Event $event): RedirectResponse
    {
        Gate::authorize('delete', $event);

        $this->deleteEvent->handle($event);

        return redirect()
            ->route('events.index')
            ->with('success', __('events.messages.deleted'));
    }
}
