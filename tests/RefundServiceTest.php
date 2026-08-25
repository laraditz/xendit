<?php

namespace Laraditz\Xendit\Tests;

use Laraditz\Xendit\Events\RefundCreated;
use Laraditz\Xendit\Models\XenditRefund;

class RefundServiceTest extends TestCase
{
    public function test_refund_created_event_carries_refund_model(): void
    {
        $refund = new XenditRefund(['refund_id' => 'rfd-1']);

        $event = new RefundCreated($refund);

        $this->assertSame($refund, $event->refund);
    }
}
