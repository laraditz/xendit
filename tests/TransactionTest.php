<?php

namespace Laraditz\Xendit\Tests;

use Illuminate\Support\Facades\Schema;
use Laraditz\Xendit\Enums\TransactionStatus;
use Laraditz\Xendit\Enums\TransactionType;
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
}
