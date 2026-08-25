<?php

namespace Laraditz\Xendit\Tests;

use Illuminate\Support\Facades\Http;
use Laraditz\Xendit\Enums\RefundReason;
use Laraditz\Xendit\Enums\RefundStatus;
use Laraditz\Xendit\Events\RefundCreated;
use Laraditz\Xendit\Models\XenditPayment;
use Laraditz\Xendit\Models\XenditRefund;
use Laraditz\Xendit\Services\RefundService;

class RefundServiceTest extends TestCase
{
    public function test_refund_created_event_carries_refund_model(): void
    {
        $refund = new XenditRefund(['refund_id' => 'rfd-1']);

        $event = new RefundCreated($refund);

        $this->assertSame($refund, $event->refund);
    }

    private function sampleResponse(array $overrides = []): array
    {
        return array_merge([
            'id' => 'rfd-create-1',
            'payment_request_id' => 'pr-create-1',
            'reference_id' => 'REFUND-001',
            'currency' => 'MYR',
            'amount' => 500,
            'status' => 'PENDING',
            'reason' => 'REQUESTED_BY_CUSTOMER',
            'failure_code' => null,
            'refund_fee_amount' => 0,
            'metadata' => ['order_id' => 123],
        ], $overrides);
    }

    public function test_create_returns_persisted_refund_model(): void
    {
        Http::fake(['*' => Http::response($this->sampleResponse(), 200)]);

        $refund = app(RefundService::class)->create([
            'payment_request_id' => 'pr-create-1',
            'reason' => RefundReason::RequestedByCustomer->value,
        ]);

        $this->assertInstanceOf(XenditRefund::class, $refund);
        $this->assertSame(1, XenditRefund::count());
        $this->assertSame('rfd-create-1', $refund->refund_id);
        $this->assertSame('pr-create-1', $refund->payment_request_id);
        $this->assertSame(RefundStatus::Pending, $refund->status);
        $this->assertEquals(500, $refund->amount);
    }

    public function test_create_resolves_payment_id_when_matching_payment_exists(): void
    {
        $payment = XenditPayment::create([
            'external_id' => 'ORDER-CREATE-1',
            'xendit_id' => 'pr-create-1',
            'payment_type' => 'PAYMENT_REQUEST',
            'amount' => 1000,
        ]);

        Http::fake(['*' => Http::response($this->sampleResponse(), 200)]);

        $refund = app(RefundService::class)->create([
            'payment_request_id' => 'pr-create-1',
            'reason' => RefundReason::RequestedByCustomer->value,
        ]);

        $this->assertSame($payment->id, $refund->payment_id);
    }

    public function test_create_leaves_payment_id_null_when_no_matching_payment(): void
    {
        Http::fake(['*' => Http::response($this->sampleResponse(), 200)]);

        $refund = app(RefundService::class)->create([
            'payment_request_id' => 'pr-create-1',
            'reason' => RefundReason::RequestedByCustomer->value,
        ]);

        $this->assertNull($refund->payment_id);
    }
}
