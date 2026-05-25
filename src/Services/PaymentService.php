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

    public function get(string $id, array $headers = []): array
    {
        return $this->client->get("/payments/{$id}", [], $headers);
    }

    public function cancel(string $id, array $headers = []): array
    {
        return $this->client->post("/payments/{$id}/cancel", [], $headers);
    }

    public function capture(string $id, array $data = [], array $headers = []): array
    {
        return $this->client->post("/payments/{$id}/capture", $data, $headers);
    }
}
