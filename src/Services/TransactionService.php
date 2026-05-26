<?php

namespace Laraditz\Xendit\Services;

use Laraditz\Xendit\Client\Concerns\HasApiVersion;
use Laraditz\Xendit\Client\XenditClient;

class TransactionService
{
    use HasApiVersion;

    protected XenditClient $client;

    public function __construct(XenditClient $client)
    {
        $this->client = $client;
        $this->apiVersionKey = 'transaction';
    }

    public function get(string $id, array $headers = []): array
    {
        return $this->client->get("/transactions/{$id}", [], $this->resolveHeaders($headers));
    }

    public function list(array $params = [], array $headers = []): array
    {
        return $this->client->get('/transactions', $params, $this->resolveHeaders($headers));
    }
}
