<?php

namespace Laraditz\Xendit\Services;

use Laraditz\Xendit\Client\XenditClient;

class PaymentService
{
    protected XenditClient $client;

    public function __construct(XenditClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get the status of a payment
     * https://docs.xendit.co/apidocs/get-payment
     */
    public function get(string $id): array
    {
        return $this->client->get("/payments/{$id}");
    }

    /**
     * Cancel a payment
     * https://docs.xendit.co/apidocs/cancel-payment
     */
    public function cancel(string $id): array
    {
        return $this->client->post("/payments/{$id}/cancel");
    }

    /**
     * Capture a payment
     * https://docs.xendit.co/apidocs/capture-payment
     */
    public function capture(string $id, array $data = []): array
    {
        return $this->client->post("/payments/{$id}/capture", $data);
    }
}
