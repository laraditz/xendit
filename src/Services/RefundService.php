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

    /**
     * Refund a payment request
     * https://docs.xendit.co/apidocs/refund-payment-request
     */
    public function create(array $data): array
    {
        return $this->client->post('/refunds', $data);
    }
}
