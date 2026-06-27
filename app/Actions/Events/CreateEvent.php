<?php

namespace App\Actions\Events;

use App\Http\Requests\Events\StoreEventRequest;
use App\Models\Event;
use App\Support\Events\EventCoverImages;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class CreateEvent
{
    public function __construct(private readonly EventCoverImages $covers) {}

    public function handle(StoreEventRequest $request): Event
    {
        $user = $request->user();

        if ($user === null) {
            throw new RuntimeException('An authenticated organizer is required to create an event.');
        }

        $cover = null;

        try {
            if ($request->hasFile('cover_image')) {
                $cover = $this->covers->upload($request->file('cover_image'));
            }

            return DB::transaction(fn (): Event => $user
                ->events()
                ->create([
                    ...$request->eventAttributes(),
                    ...($cover ?? []),
                ]));
        } catch (Throwable $exception) {
            if ($cover !== null) {
                $this->covers->delete($cover['cover_image_disk'], $cover['cover_image_key']);
            }

            throw $exception;
        }
    }
}
