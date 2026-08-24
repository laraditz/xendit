<?php

namespace Laraditz\Xendit\Tests;

use Laraditz\Xendit\Enums\RefundFailureCode;
use Laraditz\Xendit\Enums\RefundReason;
use Laraditz\Xendit\Enums\RefundStatus;

class RefundTest extends TestCase
{
    public function test_refund_reason_enum_values(): void
    {
        $this->assertSame('FRAUDULENT', RefundReason::Fraudulent->value);
        $this->assertSame('DUPLICATE', RefundReason::Duplicate->value);
        $this->assertSame('REQUESTED_BY_CUSTOMER', RefundReason::RequestedByCustomer->value);
        $this->assertSame('CANCELLATION', RefundReason::Cancellation->value);
        $this->assertSame('OTHERS', RefundReason::Others->value);
    }

    public function test_refund_status_enum_values(): void
    {
        $this->assertSame('PENDING', RefundStatus::Pending->value);
        $this->assertSame('SUCCEEDED', RefundStatus::Succeeded->value);
        $this->assertSame('FAILED', RefundStatus::Failed->value);
        $this->assertSame('CANCELLED', RefundStatus::Cancelled->value);
    }

    public function test_refund_status_helpers(): void
    {
        $this->assertTrue(RefundStatus::Pending->isPending());
        $this->assertFalse(RefundStatus::Succeeded->isPending());

        $this->assertTrue(RefundStatus::Succeeded->isSucceeded());
        $this->assertFalse(RefundStatus::Failed->isSucceeded());

        $this->assertTrue(RefundStatus::Failed->isFailed());
        $this->assertFalse(RefundStatus::Cancelled->isFailed());

        $this->assertTrue(RefundStatus::Cancelled->isCancelled());
        $this->assertFalse(RefundStatus::Pending->isCancelled());
    }

    public function test_refund_failure_code_enum_values(): void
    {
        $this->assertSame('ACCOUNT_ACCESS_BLOCKED', RefundFailureCode::AccountAccessBlocked->value);
        $this->assertSame('ACCOUNT_NOT_FOUND', RefundFailureCode::AccountNotFound->value);
        $this->assertSame('DUPLICATE_ERROR', RefundFailureCode::DuplicateError->value);
        $this->assertSame('INSUFFICIENT_BALANCE', RefundFailureCode::InsufficientBalance->value);
        $this->assertSame('REFUND_FAILED', RefundFailureCode::RefundFailed->value);
    }
}
