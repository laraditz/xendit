<?php

namespace Laraditz\Xendit\Services;

use Laraditz\Xendit\Client\XenditClient;

class CustomerService
{
    public function __construct(
        protected XenditClient $client,
    ) {}

    public function create(array $data): array
    {
        return $this->client->post('/customers', $data, [
            'idempotency-key' => $data['reference_id'],
        ]);
    }

    public function get(string $id): array
    {
        return $this->client->get("/customers/{$id}");
    }

    public function list(string $referenceId): array
    {
        return $this->client->get('/customers', ['reference_id' => $referenceId]);
    }

    public function update(string $id, array $data): array
    {
        return $this->client->patch("/customers/{$id}", $data);
    }
}
