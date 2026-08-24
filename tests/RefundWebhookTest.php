<?php

namespace Laraditz\Xendit\Tests;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Laraditz\Xendit\Events\RefundFailed;
use Laraditz\Xendit\Support\WebhookHandler;

class RefundWebhookTest extends TestCase
{
    public function test_refund_failed_event_carries_payload(): void
    {
        $payload = ['event' => 'refund.failed', 'data' => ['id' => 'rfd-1']];

        $event = new RefundFailed($payload);

        $this->assertSame($payload, $event->payload);

        $traits = class_uses($event);
        $this->assertContains(Dispatchable::class, $traits);
        $this->assertContains(SerializesModels::class, $traits);
    }

    public function test_refund_created_dead_code_is_removed(): void
    {
        $this->assertFalse(class_exists('Laraditz\\Xendit\\Events\\RefundCreated'));
        $this->assertFalse(method_exists(WebhookHandler::class, 'handleRefundCreated'));
    }
}
