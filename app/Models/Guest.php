<?php

namespace App\Models;

use App\Enums\GuestStatus;
use Database\Factories\GuestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'status',
    'adult_companions',
    'child_companions',
    'responded_at',
])]
#[Hidden([
    'invitation_token',
    'response_token_hash',
])]
class Guest extends Model
{
    /** @use HasFactory<GuestFactory> */
    use HasFactory;

    public static function booted(): void
    {
        static::creating(function (Guest $guest): void {
            if ($guest->invitation_token === null) {
                do {
                    $token = Str::random(48);
                } while (self::query()->where('invitation_token', $token)->exists());

                $guest->invitation_token = $token;
            }
        });
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function companionCount(): int
    {
        return $this->adult_companions + $this->child_companions;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GuestStatus::class,
            'adult_companions' => 'integer',
            'child_companions' => 'integer',
            'responded_at' => 'datetime',
        ];
    }
}
