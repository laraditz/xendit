<?php

namespace Laraditz\Xendit\Enums;

enum WebhookStatus: int
{
    case Pending = 0;
    case Processed = 1;
    case Failed = 2;

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Pending',
            self::Processed => 'Processed',
            self::Failed => 'Failed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending => 'warning',
            self::Processed => 'success',
            self::Failed => 'danger',
        };
    }

    public function isProcessed(): bool
    {
        return $this === self::Processed;
    }

    public function canRetry(): bool
    {
        return in_array($this, [self::Pending, self::Failed]);
    }
}
