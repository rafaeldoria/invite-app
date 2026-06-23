<?php

namespace App\Support;

use Illuminate\Support\Str;

final class EmailAddress
{
    public static function normalize(mixed $email): mixed
    {
        if (! is_string($email)) {
            return $email;
        }

        return Str::lower(trim($email));
    }
}
