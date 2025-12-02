<?php

namespace Laraditz\Xendit\Services;

use Laraditz\Xendit\Client\XenditClient;

class PaymentTokenService
{
    protected XenditClient $client;

    public function __construct(XenditClient $client)
    {
        $this->client = $client;
    }

    /**
     * Create a new payment token
     * https://docs.xendit.co/apidocs/create-payment-token
     */
    public function create(array $data): array
    {
        return $this->client->post('/payment_tokens', $data);
    }

    /**
     * Get the status of a payment token
     * https://docs.xendit.co/apidocs/get-payment-token
     */
    public function get(string $id): array
    {
        return $this->client->get("/payment_tokens/{$id}");
    }

    /**
     * Cancel and deactivate a payment token
     * https://docs.xendit.co/apidocs/cancel-payment-token
     */
    public function cancel(string $id): array
    {
        return $this->client->post("/payment_tokens/{$id}/deactivate");
    }
}
