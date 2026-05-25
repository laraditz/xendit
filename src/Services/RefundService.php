<?php

namespace Laraditz\Xendit\Services;

use Laraditz\Xendit\Client\XenditClient;

class RefundService
{
    protected XenditClient $client;

    public function __construct(XenditClient $client)
    {
        $this->client = $client;
    }

    public function create(array $data, array $headers = []): array
    {
        return $this->client->post('/refunds', $data, $headers);
    }
}
