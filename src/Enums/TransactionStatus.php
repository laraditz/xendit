<?php

namespace Laraditz\Xendit\Enums;

enum TransactionStatus: int
{
    case Pending = 0;
    case Success = 1;
    case Failed = 2;

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Pending',
            self::Success => 'Success',
            self::Failed => 'Failed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending => 'warning',
            self::Success => 'success',
            self::Failed => 'danger',
        };
    }

    public function isSuccess(): bool
    {
        return $this === self::Success;
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isFailed(): bool
    {
        return $this === self::Failed;
    }
}
