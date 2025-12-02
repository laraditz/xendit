<?php

namespace Laraditz\Xendit\Services;

use Laraditz\Xendit\Client\XenditClient;

class PaymentLinkService
{
    protected XenditClient $client;

    public function __construct(XenditClient $client)
    {
        $this->client = $client;
    }

    /**
     * Create a payment link
     * https://docs.xendit.co/apidocs/payment-link
     */
    public function create(array $data): array
    {
        return $this->client->post('/payment_links', $data);
    }

    /**
     * Get payment link by ID
     */
    public function get(string $id): array
    {
        return $this->client->get("/payment_links/{$id}");
    }
}
