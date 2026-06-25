<?php

namespace App\Support\Events;

use App\Models\Event;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class EventCoverImages
{
    public function upload(UploadedFile $file): array
    {
        $disk = (string) config('events.cover_image_disk', config('filesystems.default', 'local'));
        $extension = $file->extension() ?: match ($file->getMimeType()) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
        $key = 'event-covers/'.now()->format('Y/m').'/'.Str::uuid().'.'.$extension;
        $dimensions = @getimagesize($file->getRealPath()) ?: null;

        $path = $file->storeAs(dirname($key), basename($key), ['disk' => $disk]);

        if ($path === false) {
            throw new \RuntimeException('The cover image could not be uploaded.');
        }

        return [
            'cover_image_disk' => $disk,
            'cover_image_key' => $path,
            'cover_image_mime' => $file->getMimeType(),
            'cover_image_size' => $file->getSize(),
            'cover_image_width' => $dimensions[0] ?? null,
            'cover_image_height' => $dimensions[1] ?? null,
        ];
    }

    public function clearAttributes(): array
    {
        return [
            'cover_image_disk' => null,
            'cover_image_key' => null,
            'cover_image_mime' => null,
            'cover_image_size' => null,
            'cover_image_width' => null,
            'cover_image_height' => null,
        ];
    }

    public function delete(?string $disk, ?string $key): void
    {
        if ($disk === null || $key === null) {
            return;
        }

        try {
            Storage::disk($disk)->delete($key);
        } catch (Throwable $exception) {
            Log::warning('Event cover image cleanup failed.', [
                'disk' => $disk,
                'key' => $key,
                'exception' => $exception::class,
            ]);
        }
    }

    public function url(Event $event): ?string
    {
        if ($event->cover_image_disk === null || $event->cover_image_key === null) {
            return null;
        }

        $disk = Storage::disk($event->cover_image_disk);

        try {
            if (method_exists($disk, 'temporaryUrl')) {
                return $disk->temporaryUrl($event->cover_image_key, now()->addMinutes(30));
            }
        } catch (Throwable) {
            return $disk->url($event->cover_image_key);
        }

        return $disk->url($event->cover_image_key);
    }
}
