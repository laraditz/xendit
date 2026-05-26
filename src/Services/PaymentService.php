<?php

namespace Laraditz\Xendit\Services;

use Laraditz\Xendit\Client\Concerns\HasApiVersion;
use Laraditz\Xendit\Client\XenditClient;

class PaymentService
{
    use HasApiVersion;

    protected XenditClient $client;

    public function __construct(XenditClient $client)
    {
        $this->client = $client;
        $this->apiVersionKey = 'payment';
    }

    public function get(string $id, array $headers = []): array
    {
        return $this->client->get("/payments/{$id}", [], $this->resolveHeaders($headers));
    }

    public function cancel(string $id, array $headers = []): array
    {
        return $this->client->post("/payments/{$id}/cancel", [], $this->resolveHeaders($headers));
    }

    public function capture(string $id, array $data = [], array $headers = []): array
    {
        return $this->client->post("/payments/{$id}/capture", $data, $this->resolveHeaders($headers));
    }
}
