<?php

namespace App\Http\Controllers;

use App\Http\Requests\Events\StoreEventRequest;
use App\Http\Requests\Events\UpdateEventRequest;
use App\Models\Event;
use App\Support\Events\EventCoverImages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class EventController extends Controller
{
    public function __construct(private readonly EventCoverImages $covers) {}

    public function index(Request $request): Response
    {
        $events = $request->user()
            ->events()
            ->latest('starts_at')
            ->get()
            ->map(fn (Event $event): array => $this->summaryEvent($event));

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
            'timezoneOptions' => $this->timezoneOptions(),
            'links' => [
                'store' => route('events.store'),
                'index' => route('events.index'),
            ],
        ]);
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $cover = null;

        try {
            if ($request->hasFile('cover_image')) {
                $cover = $this->covers->upload($request->file('cover_image'));
            }

            $event = DB::transaction(fn (): Event => $request->user()
                ->events()
                ->create([
                    ...$request->eventAttributes(),
                    ...($cover ?? []),
                ]));
        } catch (Throwable $exception) {
            if ($cover !== null) {
                $this->covers->delete($cover['cover_image_disk'], $cover['cover_image_key']);
            }

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
            'event' => $this->detailEvent($event),
        ]);
    }

    public function edit(Event $event): Response
    {
        Gate::authorize('update', $event);

        return Inertia::render('Events/Edit', [
            'event' => $this->detailEvent($event),
            'timezoneOptions' => $this->timezoneOptions($event->timezone),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $oldCover = [
            'disk' => $event->cover_image_disk,
            'key' => $event->cover_image_key,
        ];
        $newCover = null;
        $replaceCover = false;

        try {
            if ($request->hasFile('cover_image')) {
                $newCover = $this->covers->upload($request->file('cover_image'));
                $replaceCover = true;
            }

            DB::transaction(function () use ($request, $event, $newCover, $replaceCover): void {
                $attributes = $request->eventAttributes();

                if ($replaceCover && $newCover !== null) {
                    $attributes = [...$attributes, ...$newCover];
                } elseif ($request->shouldRemoveCoverImage()) {
                    $attributes = [...$attributes, ...$this->covers->clearAttributes()];
                }

                $event->update($attributes);
            });
        } catch (Throwable $exception) {
            if ($newCover !== null) {
                $this->covers->delete($newCover['cover_image_disk'], $newCover['cover_image_key']);
            }

            report($exception);

            return back()
                ->withInput($request->except('cover_image'))
                ->with('error', __('events.messages.save_failed'));
        }

        if ($replaceCover || $request->shouldRemoveCoverImage()) {
            $this->covers->delete($oldCover['disk'], $oldCover['key']);
        }

        return redirect()
            ->route('events.show', $event)
            ->with('success', __('events.messages.updated'));
    }

    public function destroy(Event $event): RedirectResponse
    {
        Gate::authorize('delete', $event);

        $cover = [
            'disk' => $event->cover_image_disk,
            'key' => $event->cover_image_key,
        ];

        $event->delete();
        $this->covers->delete($cover['disk'], $cover['key']);

        return redirect()
            ->route('events.index')
            ->with('success', __('events.messages.deleted'));
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function timezoneOptions(?string $include = null): array
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
    private function summaryEvent(Event $event): array
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
    private function detailEvent(Event $event): array
    {
        return [
            ...$this->summaryEvent($event),
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
