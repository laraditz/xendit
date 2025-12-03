<?php

namespace Laraditz\Xendit\Services;

use Laraditz\Xendit\Client\XenditClient;

class PaymentRequestService
{
    protected XenditClient $client;

    public function __construct(XenditClient $client)
    {
        $this->client = $client;
    }

    /**
     * Create a payment request
     */
    public function create(array $data): array
    {
        return $this->client->post('/payment_requests', $data);
    }

    /**
     * Get payment request by ID
     */
    public function get(string $id): array
    {
        return $this->client->get("/payment_requests/{$id}");
    }

    /**
     * Cancel a payment request
     */
    public function cancel(string $id): array
    {
        return $this->client->post("/payment_requests/{$id}/cancel");
    }

    /**
     * Simulate payment (test mode only)
     */
    public function simulate(string $id, array $data = []): array
    {
        return $this->client->post("/payment_requests/{$id}/payments/simulate", $data);
    }
}
