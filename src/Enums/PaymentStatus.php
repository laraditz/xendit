<?php

namespace Laraditz\Xendit\Enums;

enum PaymentStatus: int
{
    case Pending = 0;
    case Paid = 1;
    case Expired = 2;
    case Failed = 3;
    case Cancelled = 4;

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Expired => 'Expired',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Expired => 'secondary',
            self::Failed => 'danger',
            self::Cancelled => 'secondary',
        };
    }

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Paid, self::Expired, self::Failed, self::Cancelled]);
    }
}
