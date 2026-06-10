<?php

namespace Laraditz\Xendit\Enums;

enum SettlementStatus: string
{
    case Pending = 'PENDING';
    case EarlySettled = 'EARLY_SETTLED';
    case Settled = 'SETTLED';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Pending',
            self::EarlySettled => 'Early Settled',
            self::Settled => 'Settled',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending => 'warning',
            self::EarlySettled => 'info',
            self::Settled => 'success',
        };
    }

    public function isSettled(): bool
    {
        return $this === self::Settled || $this === self::EarlySettled;
    }
}
