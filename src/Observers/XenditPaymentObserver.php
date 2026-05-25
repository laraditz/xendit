<?php

namespace Laraditz\Xendit\Observers;

use Laraditz\Xendit\Enums\PaymentStatus;
use Laraditz\Xendit\Models\XenditPayment;

class XenditPaymentObserver
{
    /**
     * Handle the XenditPayment "creating" event.
     */
    public function creating(XenditPayment $payment): void
    {
        // Set default status if not provided
        if (is_null($payment->status)) {
            $payment->status = PaymentStatus::Pending;
        }

        // Set default currency if not provided
        if (is_null($payment->currency)) {
            $payment->currency = config('xendit.default_currency', 'MYR');
        }
    }
}
