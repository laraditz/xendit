<?php

namespace Laraditz\Xendit\Services;

use Laraditz\Xendit\Client\XenditClient;

class CustomerService
{
    public function __construct(
        protected XenditClient $client,
    ) {}

    public function create(array $data, array $headers = []): array
    {
        $merged = array_merge(['idempotency-key' => $data['reference_id']], $headers);

        return $this->client->post('/customers', $data, $merged);
    }

    public function get(string $id, array $headers = []): array
    {
        return $this->client->get("/customers/{$id}", [], $headers);
    }

    public function list(string $referenceId, array $headers = []): array
    {
        return $this->client->get('/customers', ['reference_id' => $referenceId], $headers);
    }

    public function update(string $id, array $data, array $headers = []): array
    {
        return $this->client->patch("/customers/{$id}", $data, $headers);
    }
}
