<?php

namespace Laraditz\Xendit\Tests\Enums;

use Laraditz\Xendit\Enums\SettlementStatus;
use Laraditz\Xendit\Tests\TestCase;

class SettlementStatusTest extends TestCase
{
    public function test_enum_values_match_xendit_api(): void
    {
        $this->assertSame('PENDING', SettlementStatus::Pending->value);
        $this->assertSame('EARLY_SETTLED', SettlementStatus::EarlySettled->value);
        $this->assertSame('SETTLED', SettlementStatus::Settled->value);
    }

    public function test_is_settled(): void
    {
        $this->assertTrue(SettlementStatus::Settled->isSettled());
        $this->assertTrue(SettlementStatus::EarlySettled->isSettled());
        $this->assertFalse(SettlementStatus::Pending->isSettled());
    }

    public function test_label_and_color(): void
    {
        $this->assertSame('Settled', SettlementStatus::Settled->label());
        $this->assertSame('success', SettlementStatus::Settled->color());
        $this->assertSame('Early Settled', SettlementStatus::EarlySettled->label());
        $this->assertSame('Pending', SettlementStatus::Pending->label());
    }
}
