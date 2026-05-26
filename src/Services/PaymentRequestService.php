<?php

namespace Laraditz\Xendit\Services;

use Laraditz\Xendit\Client\Concerns\HasApiVersion;
use Laraditz\Xendit\Client\XenditClient;

class PaymentRequestService
{
    use HasApiVersion;

    protected XenditClient $client;

    public function __construct(XenditClient $client)
    {
        $this->client = $client;
        $this->apiVersionKey = 'payment_request';
    }

    public function create(array $data, array $headers = []): array
    {
        return $this->client->post('/v3/payment_requests', $data, $this->resolveHeaders($headers));
    }

    public function get(string $id, array $headers = []): array
    {
        return $this->client->get("/v3/payment_requests/{$id}", [], $this->resolveHeaders($headers));
    }

    public function cancel(string $id, array $headers = []): array
    {
        return $this->client->post("/v3/payment_requests/{$id}/cancel", [], $this->resolveHeaders($headers));
    }

    public function simulate(string $id, array $data = [], array $headers = []): array
    {
        return $this->client->post("/v3/payment_requests/{$id}/payments/simulate", $data, $this->resolveHeaders($headers));
    }
}
