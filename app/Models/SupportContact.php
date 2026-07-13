<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'contact',
    'subject',
    'message',
    'email_sent_at',
])]
class SupportContact extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_sent_at' => 'datetime',
        ];
    }
}
