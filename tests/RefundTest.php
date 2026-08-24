<?php

namespace Laraditz\Xendit\Tests;

use Laraditz\Xendit\Enums\RefundReason;

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
}
