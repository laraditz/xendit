<?php

namespace Laraditz\Xendit\Tests;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Event;
use Laraditz\Xendit\Events\RefundFailed;
use Laraditz\Xendit\Events\RefundSucceeded;
use Laraditz\Xendit\Models\XenditRefund;
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

    private function envelope(string $event, array $dataOverrides = []): array
    {
        return [
            'event' => $event,
            'business_id' => 'biz-1',
            'created' => '2026-06-08T02:17:33.376Z',
            'data' => array_merge([
                'id' => 'rfd-webhook-1',
                'payment_request_id' => 'pr-webhook-1',
                'reference_id' => 'REFUND-001',
                'currency' => 'MYR',
                'amount' => 500,
                'status' => 'SUCCEEDED',
                'reason' => 'REQUESTED_BY_CUSTOMER',
                'failure_code' => null,
                'refund_fee_amount' => 5,
                'metadata' => ['order_id' => 123],
            ], $dataOverrides),
        ];
    }

    public function test_refund_succeeded_webhook_syncs_and_dispatches_full_envelope(): void
    {
        Event::fake([RefundSucceeded::class]);

        $payload = $this->envelope('refund.succeeded');

        (new WebhookHandler())->handle($payload);

        $this->assertSame(1, XenditRefund::count());
        $this->assertSame('rfd-webhook-1', XenditRefund::first()->refund_id);

        Event::assertDispatched(RefundSucceeded::class, function ($event) use ($payload) {
            return $event->payload === $payload;
        });
    }
}
