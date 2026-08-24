<?php

namespace Laraditz\Xendit\Tests;

use Laraditz\Xendit\Enums\RefundReason;
use Laraditz\Xendit\Enums\RefundStatus;
use Laraditz\Xendit\Models\XenditPayment;
use Laraditz\Xendit\Models\XenditRefund;

class RefundSyncTest extends TestCase
{
    private function sampleResponse(array $overrides = []): array
    {
        return array_merge([
            'id' => 'rfd-69e77490-d2cc-4bf3-8319-e064e121db93',
            'payment_request_id' => 'pr-payment-request-1',
            'payment_id' => 'py-deprecated-1',
            'invoice_id' => null,
            'payment_method_type' => 'EWALLET',
            'reference_id' => 'REFUND-001',
            'channel_code' => 'GCASH',
            'currency' => 'PHP',
            'amount' => 500,
            'status' => 'SUCCEEDED',
            'reason' => 'REQUESTED_BY_CUSTOMER',
            'failure_code' => null,
            'refund_fee_amount' => 5,
            'metadata' => ['order_id' => 123],
            'created' => '2026-06-08T02:17:33.376Z',
            'updated' => '2026-06-08T02:17:40.000Z',
        ], $overrides);
    }

    public function test_creates_row_with_mapped_fields(): void
    {
        $refund = XenditRefund::syncFromApiResponse($this->sampleResponse());

        $this->assertNotNull($refund);
        $this->assertSame(1, XenditRefund::count());
        $this->assertSame('rfd-69e77490-d2cc-4bf3-8319-e064e121db93', $refund->refund_id);
        $this->assertSame('pr-payment-request-1', $refund->payment_request_id);
        $this->assertSame('REFUND-001', $refund->reference_id);
        $this->assertSame('PHP', $refund->currency);
        $this->assertEquals(500, $refund->amount);
        $this->assertSame(RefundStatus::Succeeded, $refund->status);
        $this->assertSame(RefundReason::RequestedByCustomer, $refund->reason);
        $this->assertNull($refund->failure_code);
        $this->assertEquals(5, $refund->refund_fee_amount);
        $this->assertSame(['order_id' => 123], $refund->metadata);
        $this->assertSame($this->sampleResponse(), $refund->raw_response);
    }

    public function test_resolves_payment_id_when_matching_payment_exists(): void
    {
        $payment = XenditPayment::create([
            'external_id' => 'ORDER-SYNC-1',
            'xendit_id' => 'pr-payment-request-1',
            'payment_type' => 'PAYMENT_REQUEST',
            'amount' => 1000,
        ]);

        $refund = XenditRefund::syncFromApiResponse($this->sampleResponse());

        $this->assertSame($payment->id, $refund->payment_id);
    }

    public function test_leaves_payment_id_null_when_no_matching_payment(): void
    {
        $refund = XenditRefund::syncFromApiResponse($this->sampleResponse());

        $this->assertNull($refund->payment_id);
    }
}
