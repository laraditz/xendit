<?php

namespace Laraditz\Xendit\Enums;

enum RefundStatus: string
{
    case Pending = 'PENDING';
    case Succeeded = 'SUCCEEDED';
    case Failed = 'FAILED';
    case Cancelled = 'CANCELLED';

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isSucceeded(): bool
    {
        return $this === self::Succeeded;
    }

    public function isFailed(): bool
    {
        return $this === self::Failed;
    }

    public function isCancelled(): bool
    {
        return $this === self::Cancelled;
    }
}
