<?php

namespace Laraditz\Xendit\Events;

use Laraditz\Xendit\Models\XenditCustomer;

class CustomerCreated
{
    public function __construct(
        public readonly XenditCustomer $customer,
    ) {}
}
