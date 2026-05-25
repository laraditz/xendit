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

    public function create(array $data, array $headers = []): array
    {
        return $this->client->post('/v3/payment_requests', $data, $headers);
    }

    public function get(string $id, array $headers = []): array
    {
        return $this->client->get("/v3/payment_requests/{$id}", [], $headers);
    }

    public function cancel(string $id, array $headers = []): array
    {
        return $this->client->post("/v3/payment_requests/{$id}/cancel", [], $headers);
    }

    public function simulate(string $id, array $data = [], array $headers = []): array
    {
        return $this->client->post("/v3/payment_requests/{$id}/payments/simulate", $data, $headers);
    }
}
