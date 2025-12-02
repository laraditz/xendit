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

    /**
     * Get transaction by ID
     * https://docs.xendit.co/apidocs/get-transaction
     */
    public function get(string $id): array
    {
        return $this->client->get("/transactions/{$id}");
    }

    /**
     * List transactions
     * https://docs.xendit.co/apidocs/list-transactions
     */
    public function list(array $params = []): array
    {
        return $this->client->get('/transactions', $params);
    }
}
