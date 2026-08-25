<?php

namespace Laraditz\Xendit\Services;

use Laraditz\Xendit\Client\Concerns\HasApiVersion;
use Laraditz\Xendit\Client\XenditClient;
use Laraditz\Xendit\Events\RefundCreated;
use Laraditz\Xendit\Models\XenditRefund;

class RefundService
{
    use HasApiVersion;

    protected XenditClient $client;

    public function __construct(XenditClient $client)
    {
        $this->client = $client;
        $this->apiVersionKey = 'refund';
    }

    public function create(array $data, array $headers = []): XenditRefund
    {
        $response = $this->client->post('/refunds', $data, $this->resolveHeaders($headers));

        $refund = XenditRefund::syncFromApiResponse($response);

        event(new RefundCreated($refund));

        return $refund;
    }
}
