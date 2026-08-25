<?php

namespace Laraditz\Xendit\Events;

use Laraditz\Xendit\Models\XenditRefund;

class RefundCreated
{
    public function __construct(
        public readonly XenditRefund $refund,
    ) {}
}
