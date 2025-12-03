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
     * Create a payment request (v3 API)
     * https://docs.xendit.co/apidocs/create-payment-request
     */
    public function create(array $data): array
    {
        return $this->client->post('/v3/payment_requests', $data);
    }

    /**
     * Get the status of a payment request (v3 API)
     * https://docs.xendit.co/apidocs/get-payment-request
     */
    public function get(string $id): array
    {
        return $this->client->get("/v3/payment_requests/{$id}");
    }

    /**
     * Cancel a payment request (v3 API)
     * https://docs.xendit.co/apidocs/cancel-payment-request
     */
    public function cancel(string $id): array
    {
        return $this->client->post("/v3/payment_requests/{$id}/cancel");
    }

    /**
     * Simulate payment (test mode only, v3 API)
     * https://docs.xendit.co/apidocs/simulate-payment-test-mode
     */
    public function simulate(string $id, array $data = []): array
    {
        return $this->client->post("/v3/payment_requests/{$id}/payments/simulate", $data);
    }
}
