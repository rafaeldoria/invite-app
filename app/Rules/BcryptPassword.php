<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class BcryptPassword implements ValidationRule
{
    private const MAX_BYTES = 72;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        if (strlen($value) > self::MAX_BYTES) {
            $fail('validation.password.bcrypt_bytes')->translate([
                'max' => self::MAX_BYTES,
            ]);
        }
    }
}
