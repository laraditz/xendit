<?php

namespace Laraditz\Xendit\Services;

use Laraditz\Xendit\Client\Concerns\HasApiVersion;
use Laraditz\Xendit\Client\XenditClient;

class SessionService
{
    use HasApiVersion;

    protected XenditClient $client;

    public function __construct(XenditClient $client)
    {
        $this->client = $client;
        $this->apiVersionKey = 'session';
    }

    public function create(array $data, array $headers = []): array
    {
        return $this->client->post('/sessions', $data, $this->resolveHeaders($headers));
    }

    public function get(string $id, array $headers = []): array
    {
        return $this->client->get("/sessions/{$id}", [], $this->resolveHeaders($headers));
    }

    public function cancel(string $id, array $headers = []): array
    {
        return $this->client->post("/sessions/{$id}/cancel", [], $this->resolveHeaders($headers));
    }
}
