<?php

namespace App\Support;

final class Locale
{
    public const DEFAULT = 'pt-BR';

    public const ENGLISH = 'en-US';

    /**
     * @return list<string>
     */
    public static function supported(): array
    {
        return [self::DEFAULT, self::ENGLISH];
    }

    public static function normalize(mixed $locale): string
    {
        return is_string($locale) && in_array($locale, self::supported(), true)
            ? $locale
            : self::DEFAULT;
    }
}
