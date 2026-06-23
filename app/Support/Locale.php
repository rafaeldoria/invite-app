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

    public static function fromBrowserPreference(?string $locale): string
    {
        $normalized = str_replace('_', '-', strtolower($locale ?? ''));

        return match (true) {
            str_starts_with($normalized, 'en') => self::ENGLISH,
            str_starts_with($normalized, 'pt') => self::DEFAULT,
            default => self::DEFAULT,
        };
    }
}
