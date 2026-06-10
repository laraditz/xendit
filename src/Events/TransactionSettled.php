<?php

namespace Laraditz\Xendit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Laraditz\Xendit\Models\XenditTransaction;

class TransactionSettled
{
    use Dispatchable, SerializesModels;

    public function __construct(public XenditTransaction $transaction)
    {
    }
}
