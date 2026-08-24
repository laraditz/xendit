<?php

namespace Laraditz\Xendit\Tests;

use Illuminate\Support\Facades\Schema;
use Laraditz\Xendit\Enums\RefundFailureCode;
use Laraditz\Xendit\Enums\RefundReason;
use Laraditz\Xendit\Enums\RefundStatus;
use Laraditz\Xendit\Models\XenditPayment;
use Laraditz\Xendit\Models\XenditRefund;

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

    public function test_xendit_refunds_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('xendit_refunds'));
    }

    public function test_xendit_refunds_table_has_expected_columns(): void
    {
        $columns = Schema::getColumnListing('xendit_refunds');

        foreach ([
            'id', 'payment_id', 'refund_id', 'payment_request_id',
            'reference_id', 'currency', 'amount', 'status', 'reason',
            'failure_code', 'refund_fee_amount', 'metadata', 'raw_response',
            'created_at', 'updated_at', 'deleted_at',
        ] as $column) {
            $this->assertContains($column, $columns, "Missing column: $column");
        }
    }

    public function test_xendit_refund_model_casts(): void
    {
        $payment = XenditPayment::create([
            'external_id' => 'ORDER-REFUND-1',
            'xendit_id' => 'pr-payment-request-1',
            'payment_type' => 'PAYMENT_REQUEST',
            'amount' => 1000,
        ]);

        $refund = XenditRefund::create([
            'payment_id' => $payment->id,
            'refund_id' => 'rfd-1',
            'payment_request_id' => 'pr-payment-request-1',
            'reference_id' => 'REFUND-001',
            'currency' => 'MYR',
            'amount' => 500,
            'status' => RefundStatus::Succeeded,
            'reason' => RefundReason::RequestedByCustomer,
            'failure_code' => null,
            'refund_fee_amount' => 5,
            'metadata' => ['order_id' => 123],
            'raw_response' => ['id' => 'rfd-1'],
        ]);

        $refund->refresh();

        $this->assertInstanceOf(RefundStatus::class, $refund->status);
        $this->assertSame(RefundStatus::Succeeded, $refund->status);
        $this->assertInstanceOf(RefundReason::class, $refund->reason);
        $this->assertSame(RefundReason::RequestedByCustomer, $refund->reason);
        $this->assertIsArray($refund->metadata);
        $this->assertSame(['order_id' => 123], $refund->metadata);
        $this->assertIsArray($refund->raw_response);
        $this->assertEquals(500, $refund->amount);
        $this->assertEquals(5, $refund->refund_fee_amount);
        $this->assertTrue($refund->payment->is($payment));
    }

    public function test_xendit_refund_soft_deletes(): void
    {
        $refund = XenditRefund::create([
            'refund_id' => 'rfd-soft-delete',
            'payment_request_id' => 'pr-1',
            'amount' => 100,
            'status' => RefundStatus::Pending,
        ]);

        $refund->delete();

        $this->assertSoftDeleted('xendit_refunds', ['refund_id' => 'rfd-soft-delete']);
    }
}
