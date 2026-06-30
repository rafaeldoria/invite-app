<?php

namespace App\Models;

use Database\Factories\GuestCompanionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name',
    'is_child',
])]
class GuestCompanion extends Model
{
    /** @use HasFactory<GuestCompanionFactory> */
    use HasFactory;

    /** @return BelongsTo<Guest, $this> */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_child' => 'boolean',
        ];
    }
}
