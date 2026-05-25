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

    public function create(array $data, array $headers = []): array
    {
        return $this->client->post('/payment_tokens', $data, $headers);
    }

    public function get(string $id, array $headers = []): array
    {
        return $this->client->get("/payment_tokens/{$id}", [], $headers);
    }

    public function cancel(string $id, array $headers = []): array
    {
        return $this->client->post("/payment_tokens/{$id}/deactivate", [], $headers);
    }
}
