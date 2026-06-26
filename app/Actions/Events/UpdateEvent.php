<?php

namespace App\Actions\Events;

use App\Http\Requests\Events\UpdateEventRequest;
use App\Models\Event;
use App\Support\Events\EventCoverImages;
use Illuminate\Support\Facades\DB;
use Throwable;

final class UpdateEvent
{
    public function __construct(private readonly EventCoverImages $covers) {}

    public function handle(UpdateEventRequest $request, Event $event): void
    {
        $oldCover = [
            'disk' => $event->cover_image_disk,
            'key' => $event->cover_image_key,
        ];
        $newCover = null;
        $replaceCover = false;
        $removeCover = $request->shouldRemoveCoverImage();

        try {
            if ($request->hasFile('cover_image')) {
                $newCover = $this->covers->upload($request->file('cover_image'));
                $replaceCover = true;
            }

            DB::transaction(function () use ($request, $event, $newCover, $replaceCover, $removeCover): void {
                $attributes = $request->eventAttributes();

                if ($replaceCover && $newCover !== null) {
                    $attributes = [...$attributes, ...$newCover];
                } elseif ($removeCover) {
                    $attributes = [...$attributes, ...$this->covers->clearAttributes()];
                }

                $event->update($attributes);
            });
        } catch (Throwable $exception) {
            if ($newCover !== null) {
                $this->covers->delete($newCover['cover_image_disk'], $newCover['cover_image_key']);
            }

            throw $exception;
        }

        if ($replaceCover || $removeCover) {
            $this->covers->delete($oldCover['disk'], $oldCover['key']);
        }
    }
}
