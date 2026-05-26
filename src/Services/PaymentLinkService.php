<?php

namespace Laraditz\Xendit\Services;

use Laraditz\Xendit\Client\Concerns\HasApiVersion;
use Laraditz\Xendit\Client\XenditClient;

class PaymentLinkService
{
    use HasApiVersion;

    protected XenditClient $client;

    public function __construct(XenditClient $client)
    {
        $this->client = $client;
        $this->apiVersionKey = 'payment_link';
    }

    public function create(array $data, array $headers = []): array
    {
        return $this->client->post('/payment_links', $data, $this->resolveHeaders($headers));
    }

    public function get(string $id, array $headers = []): array
    {
        return $this->client->get("/payment_links/{$id}", [], $this->resolveHeaders($headers));
    }
}
