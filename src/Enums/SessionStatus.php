<?php

namespace Laraditz\Xendit\Enums;

enum SessionStatus: int
{
    case Active    = 1;
    case Completed = 2;
    case Expired   = 3;
    case Canceled  = 4;

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Expired, self::Canceled]);
    }

    public function label(): string
    {
        return match($this) {
            self::Active    => 'Active',
            self::Completed => 'Completed',
            self::Expired   => 'Expired',
            self::Canceled  => 'Canceled',
        };
    }
}
