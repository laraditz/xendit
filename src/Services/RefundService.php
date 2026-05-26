<?php

namespace Laraditz\Xendit\Services;

use Laraditz\Xendit\Client\Concerns\HasApiVersion;
use Laraditz\Xendit\Client\XenditClient;

class RefundService
{
    use HasApiVersion;

    protected XenditClient $client;

    public function __construct(XenditClient $client)
    {
        $this->client = $client;
        $this->apiVersionKey = 'refund';
    }

    public function create(array $data, array $headers = []): array
    {
        return $this->client->post('/refunds', $data, $this->resolveHeaders($headers));
    }
}
