<?php

namespace Laraditz\Xendit\Enums;

enum TransactionStatus: string
{
    case Pending = 'PENDING';
    case Success = 'SUCCESS';
    case Failed = 'FAILED';
    case Voided = 'VOIDED';
    case Reversed = 'REVERSED';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Pending',
            self::Success => 'Success',
            self::Failed => 'Failed',
            self::Voided => 'Voided',
            self::Reversed => 'Reversed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending => 'warning',
            self::Success => 'success',
            self::Failed => 'danger',
            self::Voided => 'secondary',
            self::Reversed => 'secondary',
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

    public function isVoided(): bool
    {
        return $this === self::Voided;
    }

    public function isReversed(): bool
    {
        return $this === self::Reversed;
    }
}
