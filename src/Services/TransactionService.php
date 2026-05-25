<?php

namespace Laraditz\Xendit\Services;

use Laraditz\Xendit\Client\XenditClient;

class TransactionService
{
    protected XenditClient $client;

    public function __construct(XenditClient $client)
    {
        $this->client = $client;
    }

    public function get(string $id, array $headers = []): array
    {
        return $this->client->get("/transactions/{$id}", [], $headers);
    }

    public function list(array $params = [], array $headers = []): array
    {
        return $this->client->get('/transactions', $params, $headers);
    }
}
