<?php

namespace App\Enums;

enum GuestStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Declined = 'declined';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function allowsCompanions(): bool
    {
        return $this === self::Confirmed;
    }
}
