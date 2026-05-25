<?php

namespace Laraditz\Xendit\Observers;

use Laraditz\Xendit\Enums\TransactionStatus;
use Laraditz\Xendit\Models\XenditTransaction;

class XenditTransactionObserver
{
    /**
     * Handle the XenditTransaction "creating" event.
     */
    public function creating(XenditTransaction $transaction): void
    {
        // Set default status if not provided
        if (is_null($transaction->status)) {
            $transaction->status = TransactionStatus::Pending;
        }

        // Set default currency if not provided
        if (is_null($transaction->currency)) {
            $transaction->currency = config('xendit.default_currency', 'MYR');
        }
    }
}
