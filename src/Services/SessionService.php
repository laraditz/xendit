<?php

namespace Laraditz\Xendit\Services;

use Laraditz\Xendit\Client\XenditClient;

class SessionService
{
    protected XenditClient $client;

    public function __construct(XenditClient $client)
    {
        $this->client = $client;
    }

    public function create(array $data, array $headers = []): array
    {
        return $this->client->post('/sessions', $data, $headers);
    }

    public function get(string $id, array $headers = []): array
    {
        return $this->client->get("/sessions/{$id}", [], $headers);
    }

    public function cancel(string $id, array $headers = []): array
    {
        return $this->client->post("/sessions/{$id}/cancel", [], $headers);
    }
}
