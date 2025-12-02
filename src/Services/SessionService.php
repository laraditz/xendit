<?php

namespace Laraditz\Xendit\Services;

use Laraditz\Xendit\Client\XenditClient;

class SessionService
{
    protected XenditClient $client;

    public function __construct(XenditClient $client)
    {
        $this->client = $client;
    }

    /**
     * Create a session
     * https://docs.xendit.co/apidocs/create-session
     */
    public function create(array $data): array
    {
        return $this->client->post('/sessions', $data);
    }

    /**
     * Get the status of a session
     * https://docs.xendit.co/apidocs/get-session
     */
    public function get(string $id): array
    {
        return $this->client->get("/sessions/{$id}");
    }

    /**
     * Cancel a session
     * https://docs.xendit.co/apidocs/cancel-session
     */
    public function cancel(string $id): array
    {
        return $this->client->post("/sessions/{$id}/cancel");
    }
}
