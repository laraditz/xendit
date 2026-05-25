<?php

namespace Laraditz\Xendit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Laraditz\Xendit\Builders\PaymentRequestBuilder paymentRequest()
 * @method static \Laraditz\Xendit\Services\PaymentService payment()
 * @method static \Laraditz\Xendit\Services\PaymentTokenService paymentToken()
 * @method static \Laraditz\Xendit\Services\SessionService session()
 * @method static \Laraditz\Xendit\Services\RefundService refund()
 * @method static \Laraditz\Xendit\Services\PaymentLinkService paymentLink()
 * @method static \Laraditz\Xendit\Services\TransactionService transaction()
 * @method static \Laraditz\Xendit\Client\XenditClient client()
 *
 * @see \Laraditz\Xendit\Xendit
 */
class Xendit extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'xendit';
    }
}
