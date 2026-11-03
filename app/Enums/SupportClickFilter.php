<?php

namespace App\Enums;

/**
 * Why a recorded support click is left out of the click counts.
 */
enum SupportClickFilter: string
{
    case Bot = 'bot';
    case Duplicate = 'duplicate';

    public function label(): string
    {
        return match ($this) {
            self::Bot => 'Bot or automated',
            self::Duplicate => 'Repeat within seconds',
        };
    }
}
