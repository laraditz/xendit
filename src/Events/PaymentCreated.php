<?php

namespace Laraditz\Xendit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Laraditz\Xendit\Models\XenditPayment;

class PaymentCreated
{
    use Dispatchable, SerializesModels;

    public XenditPayment $payment;

    public function __construct(XenditPayment $payment)
    {
        $this->payment = $payment;
    }
}
