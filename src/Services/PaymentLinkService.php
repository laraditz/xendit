<?php

namespace Laraditz\Xendit\Services;

use Laraditz\Xendit\Client\XenditClient;

class PaymentLinkService
{
    protected XenditClient $client;

    public function __construct(XenditClient $client)
    {
        $this->client = $client;
    }

    public function create(array $data, array $headers = []): array
    {
        return $this->client->post('/payment_links', $data, $headers);
    }

    public function get(string $id, array $headers = []): array
    {
        return $this->client->get("/payment_links/{$id}", [], $headers);
    }
}
