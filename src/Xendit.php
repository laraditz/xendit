<?php

namespace Laraditz\Xendit;

use Laraditz\Xendit\Builders\CustomerBuilder;
use Laraditz\Xendit\Builders\PaymentRequestBuilder;
use Laraditz\Xendit\Client\XenditClient;
use Laraditz\Xendit\Services\CustomerService;
use Laraditz\Xendit\Services\PaymentLinkService;
use Laraditz\Xendit\Services\PaymentRequestService;
use Laraditz\Xendit\Services\PaymentService;
use Laraditz\Xendit\Services\PaymentTokenService;
use Laraditz\Xendit\Services\RefundService;
use Laraditz\Xendit\Services\SessionService;
use Laraditz\Xendit\Services\TransactionService;

class Xendit
{
    protected XenditClient $client;

    public function __construct(XenditClient $client)
    {
        $this->client = $client;
    }

    public function customer(): CustomerBuilder
    {
        return new CustomerBuilder(new CustomerService($this->client));
    }

    /**
     * Create a payment request (unified payment method)
     * https://docs.xendit.co/apidocs/create-payment-request
     */
    public function paymentRequest(): PaymentRequestBuilder
    {
        return new PaymentRequestBuilder(
            new PaymentRequestService($this->client)
        );
    }

    /**
     * Access payment operations (get, cancel, capture)
     * https://docs.xendit.co/apidocs/get-payment
     */
    public function payment(): PaymentService
    {
        return new PaymentService($this->client);
    }

    /**
     * Access payment token operations (create, get, cancel)
     * https://docs.xendit.co/apidocs/create-payment-token
     */
    public function paymentToken(): PaymentTokenService
    {
        return new PaymentTokenService($this->client);
    }

    /**
     * Access session operations (create, get, cancel)
     * https://docs.xendit.co/apidocs/create-session
     */
    public function session(): SessionService
    {
        return new SessionService($this->client);
    }

    /**
     * Access refund operations
     * https://docs.xendit.co/apidocs/refund-payment-request
     */
    public function refund(): RefundService
    {
        return new RefundService($this->client);
    }

    /**
     * Access payment link operations
     * https://docs.xendit.co/apidocs/payment-link
     */
    public function paymentLink(): PaymentLinkService
    {
        return new PaymentLinkService($this->client);
    }

    /**
     * Access transaction operations
     * https://docs.xendit.co/apidocs/get-transaction
     */
    public function transaction(): TransactionService
    {
        return new TransactionService($this->client);
    }

    /**
     * Get the HTTP client instance
     */
    public function client(): XenditClient
    {
        return $this->client;
    }
}
