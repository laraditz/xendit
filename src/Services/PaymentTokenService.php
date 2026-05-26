<?php

namespace Laraditz\Xendit\Services;

use Laraditz\Xendit\Client\Concerns\HasApiVersion;
use Laraditz\Xendit\Client\XenditClient;

class PaymentTokenService
{
    use HasApiVersion;

    protected XenditClient $client;

    public function __construct(XenditClient $client)
    {
        $this->client = $client;
        $this->apiVersionKey = 'payment_token';
    }

    public function create(array $data, array $headers = []): array
    {
        return $this->client->post('/payment_tokens', $data, $this->resolveHeaders($headers));
    }

    public function get(string $id, array $headers = []): array
    {
        return $this->client->get("/payment_tokens/{$id}", [], $this->resolveHeaders($headers));
    }

    public function cancel(string $id, array $headers = []): array
    {
        return $this->client->post("/payment_tokens/{$id}/deactivate", [], $this->resolveHeaders($headers));
    }
}
