<?php

namespace Laraditz\Xendit\Observers;

use Laraditz\Xendit\Enums\CustomerType;
use Laraditz\Xendit\Models\XenditCustomer;

class XenditCustomerObserver
{
    public function creating(XenditCustomer $customer): void
    {
        if (is_null($customer->type)) {
            $customer->type = CustomerType::Individual;
        }
    }
}
