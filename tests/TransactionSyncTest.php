<?php

namespace Laraditz\Xendit\Tests;

use Illuminate\Support\Facades\Event;
use Laraditz\Xendit\Enums\SettlementStatus;
use Laraditz\Xendit\Events\TransactionSettled;
use Laraditz\Xendit\Models\XenditPayment;
use Laraditz\Xendit\Models\XenditTransaction;

class TransactionSyncTest extends TestCase
{
    private function sampleResponse(array $overrides = []): array
    {
        return array_merge([
            'id' => 'txn_cd1c10b6-e7f7-4037-a887-eeb2ca11a8d6',
            'product_id' => 'py-821e4c20-101d-4267-a137-70058a6d441d',
            'type' => 'PAYMENT',
            'status' => 'SUCCESS',
            'channel_category' => 'DIRECT_DEBIT',
            'channel_code' => 'HSBC_FPX',
            'reference_id' => 'ORDER-123',
            'currency' => 'MYR',
            'amount' => 1520,
            'net_amount' => 1518.8,
            'cashflow' => 'MONEY_IN',
            'settlement_status' => 'PENDING',
            'fee' => [
                'xendit_fee' => 1.2,
                'value_added_tax' => 0,
            ],
            'payment_date' => '2026-06-08T02:17:33.376Z',
            'actual_settlement_date' => null,
        ], $overrides);
    }

    public function test_returns_null_and_creates_nothing_when_no_matching_payment(): void
    {
        $result = XenditTransaction::syncFromApiResponse($this->sampleResponse());

        $this->assertNull($result);
        $this->assertSame(0, XenditTransaction::count());
    }

    public function test_creates_new_row_when_matching_payment_exists(): void
    {
        $payment = XenditPayment::create([
            'external_id' => 'ORDER-123',
            'payment_type' => 'PAYMENT_REQUEST',
            'amount' => 1520,
        ]);

        $transaction = XenditTransaction::syncFromApiResponse($this->sampleResponse());

        $this->assertNotNull($transaction);
        $this->assertSame($payment->id, $transaction->payment_id);
        $this->assertSame('txn_cd1c10b6-e7f7-4037-a887-eeb2ca11a8d6', $transaction->transaction_id);
        $this->assertSame('ORDER-123', $transaction->reference_id);
        $this->assertSame('PAYMENT', $transaction->getRawOriginal('type'));
        $this->assertSame('SUCCESS', $transaction->getRawOriginal('status'));
        $this->assertSame('HSBC_FPX', $transaction->payment_method);
        $this->assertEquals(1.2, $transaction->fee);
        $this->assertEquals(1518.8, $transaction->net_amount);
        $this->assertEquals(SettlementStatus::Pending, $transaction->settlement_status);
        $this->assertNull($transaction->settled_at);
        $this->assertNotNull($transaction->completed_at);
        $this->assertSame($this->sampleResponse(), $transaction->raw_response);
    }

    public function test_updates_existing_row_matched_by_transaction_id(): void
    {
        $payment = XenditPayment::create([
            'external_id' => 'ORDER-123',
            'payment_type' => 'PAYMENT_REQUEST',
            'amount' => 1520,
        ]);

        XenditTransaction::syncFromApiResponse($this->sampleResponse(['settlement_status' => 'PENDING']));

        $this->assertSame(1, XenditTransaction::count());

        $updated = XenditTransaction::syncFromApiResponse($this->sampleResponse([
            'settlement_status' => 'PENDING',
            'amount' => 2000,
        ]));

        $this->assertSame(1, XenditTransaction::count());
        $this->assertEquals(2000, $updated->amount);
        $this->assertSame($payment->id, $updated->payment_id);
    }

    public function test_transitioning_to_settled_calls_mark_as_settled_and_fires_event(): void
    {
        Event::fake([TransactionSettled::class]);

        XenditPayment::create([
            'external_id' => 'ORDER-123',
            'payment_type' => 'PAYMENT_REQUEST',
            'amount' => 1520,
        ]);

        XenditTransaction::syncFromApiResponse($this->sampleResponse(['settlement_status' => 'PENDING']));

        Event::assertNotDispatched(TransactionSettled::class);

        $settled = XenditTransaction::syncFromApiResponse($this->sampleResponse([
            'settlement_status' => 'SETTLED',
            'actual_settlement_date' => '2026-06-10T02:18:08.685Z',
        ]));

        $this->assertEquals(SettlementStatus::Settled, $settled->settlement_status);
        $this->assertNotNull($settled->settled_at);

        Event::assertDispatched(TransactionSettled::class, function ($event) use ($settled) {
            return $event->transaction->is($settled);
        });
    }

    public function test_resyncing_already_settled_transaction_does_not_refire_event(): void
    {
        Event::fake([TransactionSettled::class]);

        XenditPayment::create([
            'external_id' => 'ORDER-123',
            'payment_type' => 'PAYMENT_REQUEST',
            'amount' => 1520,
        ]);

        XenditTransaction::syncFromApiResponse($this->sampleResponse([
            'settlement_status' => 'SETTLED',
            'actual_settlement_date' => '2026-06-10T02:18:08.685Z',
        ]));

        Event::assertDispatchedTimes(TransactionSettled::class, 1);

        XenditTransaction::syncFromApiResponse($this->sampleResponse([
            'settlement_status' => 'SETTLED',
            'actual_settlement_date' => '2026-06-10T02:18:08.685Z',
        ]));

        Event::assertDispatchedTimes(TransactionSettled::class, 1);
    }
}
