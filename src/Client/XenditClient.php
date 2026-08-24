<?php

namespace Laraditz\Xendit\Client;

use Laraditz\Xendit\Client\Concerns\HandlesAuthentication;
use Laraditz\Xendit\Client\Concerns\HandlesErrors;
use Laraditz\Xendit\Client\Concerns\LogsApiCalls;
use Laraditz\Xendit\Client\Concerns\MakesHttpRequests;
use Laraditz\Xendit\Client\Contracts\ClientInterface;
use Laraditz\Xendit\Events\XenditApiRequesting;
use Laraditz\Xendit\Events\XenditApiResponseReceived;

class XenditClient implements ClientInterface
{
    use HandlesAuthentication;
    use HandlesErrors;
    use LogsApiCalls;
    use MakesHttpRequests;

    public function get(string $endpoint, array $query = [], array $headers = []): array
    {
        return $this->request('GET', $endpoint, $query, [], $headers);
    }

    public function post(string $endpoint, array $data = [], array $headers = []): array
    {
        return $this->request('POST', $endpoint, [], $data, $headers);
    }

    public function put(string $endpoint, array $data = [], array $headers = []): array
    {
        return $this->request('PUT', $endpoint, [], $data, $headers);
    }

    public function patch(string $endpoint, array $data = [], array $headers = []): array
    {
        return $this->request('PATCH', $endpoint, [], $data, $headers);
    }

    public function delete(string $endpoint, array $headers = []): array
    {
        return $this->request('DELETE', $endpoint, [], [], $headers);
    }

    protected function request(string $method, string $endpoint, array $query, array $payload, array $headers): array
    {
        $startedAt = microtime(true);
        $response = null;

        event(new XenditApiRequesting($method, $endpoint, $query, $payload));

        try {
            $response = match ($method) {
                'GET' => $this->buildClient()->withHeaders($headers)->get($endpoint, $query),
                'DELETE' => $this->buildClient()->withHeaders($headers)->delete($endpoint),
                default => $this->buildClient()->withHeaders($headers)->{strtolower($method)}($endpoint, $payload),
            };

            $result = $this->handleResponse($response);

            event(new XenditApiResponseReceived($method, $endpoint, $query, $payload, $result));

            return $result;
        } finally {
            $this->logApiCall($method, $endpoint, $query, $payload, $response, $startedAt);
        }
    }
}
