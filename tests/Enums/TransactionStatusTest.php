<?php

namespace Laraditz\Xendit\Tests\Enums;

use Laraditz\Xendit\Enums\TransactionStatus;
use Laraditz\Xendit\Tests\TestCase;

class TransactionStatusTest extends TestCase
{
    public function test_enum_values_match_xendit_api(): void
    {
        $this->assertSame('PENDING', TransactionStatus::Pending->value);
        $this->assertSame('SUCCESS', TransactionStatus::Success->value);
        $this->assertSame('FAILED', TransactionStatus::Failed->value);
        $this->assertSame('VOIDED', TransactionStatus::Voided->value);
        $this->assertSame('REVERSED', TransactionStatus::Reversed->value);
    }

    public function test_is_helpers(): void
    {
        $this->assertTrue(TransactionStatus::Success->isSuccess());
        $this->assertTrue(TransactionStatus::Pending->isPending());
        $this->assertTrue(TransactionStatus::Failed->isFailed());
        $this->assertTrue(TransactionStatus::Voided->isVoided());
        $this->assertTrue(TransactionStatus::Reversed->isReversed());

        $this->assertFalse(TransactionStatus::Success->isFailed());
    }

    public function test_label_and_color(): void
    {
        $this->assertSame('Success', TransactionStatus::Success->label());
        $this->assertSame('success', TransactionStatus::Success->color());
        $this->assertSame('Voided', TransactionStatus::Voided->label());
        $this->assertSame('Reversed', TransactionStatus::Reversed->label());
    }
}
