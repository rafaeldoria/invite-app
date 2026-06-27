<?php

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'description',
    'starts_at',
    'timezone',
    'location',
    'theme',
    'share_message',
    'cover_image_disk',
    'cover_image_key',
    'cover_image_mime',
    'cover_image_size',
    'cover_image_width',
    'cover_image_height',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    public static function booted(): void
    {
        static::creating(function (Event $event): void {
            if ($event->public_id === null) {
                $event->public_id = (string) Str::ulid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return Attribute<string, never> */
    protected function localStartsDate(): Attribute
    {
        return Attribute::get(fn (): string => $this->starts_at->copy()->setTimezone($this->timezone)->format('Y-m-d'));
    }

    /** @return Attribute<string, never> */
    protected function localStartsTime(): Attribute
    {
        return Attribute::get(fn (): string => $this->starts_at->copy()->setTimezone($this->timezone)->format('H:i'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'cover_image_size' => 'integer',
            'cover_image_width' => 'integer',
            'cover_image_height' => 'integer',
        ];
    }
}
