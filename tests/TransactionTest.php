<?php

namespace Laraditz\Xendit\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Laraditz\Xendit\Enums\SettlementStatus;
use Laraditz\Xendit\Enums\TransactionStatus;
use Laraditz\Xendit\Enums\TransactionType;
use Laraditz\Xendit\Events\TransactionSettled;
use Laraditz\Xendit\Models\XenditTransaction;

class TransactionTest extends TestCase
{
    public function test_status_column_is_string(): void
    {
        $type = Schema::getColumnType('xendit_transactions', 'status');

        $this->assertSame('varchar', $type);
    }

    public function test_status_and_type_scopes_use_new_enum_values(): void
    {
        XenditTransaction::create([
            'payment_id' => 1,
            'transaction_id' => 'txn_success_payment',
            'type' => TransactionType::Payment,
            'status' => TransactionStatus::Success,
            'amount' => 100,
            'net_amount' => 99,
        ]);

        XenditTransaction::create([
            'payment_id' => 1,
            'transaction_id' => 'txn_pending_refund',
            'type' => TransactionType::Refund,
            'status' => TransactionStatus::Pending,
            'amount' => 50,
            'net_amount' => 50,
        ]);

        $this->assertCount(1, XenditTransaction::success()->get());
        $this->assertCount(1, XenditTransaction::payments()->get());
        $this->assertCount(1, XenditTransaction::refunds()->get());

        $success = XenditTransaction::success()->first();
        $this->assertSame('SUCCESS', $success->getRawOriginal('status'));
        $this->assertSame('PAYMENT', $success->getRawOriginal('type'));
    }

    public function test_settlement_status_and_settled_at_columns_exist(): void
    {
        $columns = Schema::getColumnListing('xendit_transactions');

        $this->assertContains('settlement_status', $columns);
        $this->assertContains('settled_at', $columns);
    }

    public function test_reference_id_column_exists(): void
    {
        $columns = Schema::getColumnListing('xendit_transactions');

        $this->assertContains('reference_id', $columns);

        XenditTransaction::create([
            'payment_id' => 1,
            'transaction_id' => 'txn_with_reference',
            'reference_id' => 'ORDER-123',
            'type' => TransactionType::Payment,
            'status' => TransactionStatus::Success,
            'amount' => 100,
            'net_amount' => 99,
        ]);

        $this->assertSame(
            'ORDER-123',
            XenditTransaction::where('transaction_id', 'txn_with_reference')->first()->reference_id
        );
    }

    public function test_settlement_casts_and_scopes(): void
    {
        XenditTransaction::create([
            'payment_id' => 1,
            'transaction_id' => 'txn_settled',
            'type' => TransactionType::Payment,
            'status' => TransactionStatus::Success,
            'amount' => 100,
            'net_amount' => 99,
            'settlement_status' => SettlementStatus::Settled,
        ]);

        XenditTransaction::create([
            'payment_id' => 1,
            'transaction_id' => 'txn_pending_settlement',
            'type' => TransactionType::Payment,
            'status' => TransactionStatus::Success,
            'amount' => 100,
            'net_amount' => 99,
            'settlement_status' => SettlementStatus::Pending,
        ]);

        $settled = XenditTransaction::where('transaction_id', 'txn_settled')->first();
        $pending = XenditTransaction::where('transaction_id', 'txn_pending_settlement')->first();

        $this->assertInstanceOf(SettlementStatus::class, $settled->settlement_status);
        $this->assertTrue($settled->isSettled());
        $this->assertFalse($pending->isSettled());
        $this->assertTrue($pending->isPendingSettlement());

        $this->assertCount(1, XenditTransaction::settled()->get());
        $this->assertCount(1, XenditTransaction::pendingSettlement()->get());
        $this->assertCount(1, XenditTransaction::settlementStatus(SettlementStatus::Settled)->get());
    }

    public function test_mark_as_settled_updates_fields_and_fires_event(): void
    {
        Event::fake([TransactionSettled::class]);

        $transaction = XenditTransaction::create([
            'payment_id' => 1,
            'transaction_id' => 'txn_to_settle',
            'type' => TransactionType::Payment,
            'status' => TransactionStatus::Success,
            'amount' => 100,
            'net_amount' => 99,
            'settlement_status' => SettlementStatus::Pending,
        ]);

        $transaction->markAsSettled();

        $transaction->refresh();

        $this->assertEquals(SettlementStatus::Settled, $transaction->settlement_status);
        $this->assertNotNull($transaction->settled_at);

        Event::assertDispatched(TransactionSettled::class, function ($event) use ($transaction) {
            return $event->transaction->is($transaction);
        });
    }
}
