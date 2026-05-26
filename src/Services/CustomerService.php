<?php

namespace Laraditz\Xendit\Services;

use Laraditz\Xendit\Client\Concerns\HasApiVersion;
use Laraditz\Xendit\Client\XenditClient;

class CustomerService
{
    use HasApiVersion;

    public function __construct(
        protected XenditClient $client,
    ) {
        $this->apiVersionKey = 'customer';
    }

    public function create(array $data, array $headers = []): array
    {
        $resolved = $this->resolveHeaders($headers);
        $merged = array_merge(['idempotency-key' => $data['reference_id']], $resolved);

        return $this->client->post('/customers', $data, $merged);
    }

    public function get(string $id, array $headers = []): array
    {
        return $this->client->get("/customers/{$id}", [], $this->resolveHeaders($headers));
    }

    public function list(string $referenceId, array $headers = []): array
    {
        return $this->client->get('/customers', ['reference_id' => $referenceId], $this->resolveHeaders($headers));
    }

    public function update(string $id, array $data, array $headers = []): array
    {
        return $this->client->patch("/customers/{$id}", $data, $this->resolveHeaders($headers));
    }
}
