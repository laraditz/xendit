<?php

namespace Laraditz\Xendit\Client;

use Laraditz\Xendit\Client\Concerns\HandlesAuthentication;
use Laraditz\Xendit\Client\Concerns\HandlesErrors;
use Laraditz\Xendit\Client\Concerns\MakesHttpRequests;
use Laraditz\Xendit\Client\Contracts\ClientInterface;

class XenditClient implements ClientInterface
{
    use HandlesAuthentication;
    use HandlesErrors;
    use MakesHttpRequests;

    public function get(string $endpoint, array $query = [], array $headers = []): array
    {
        $response = $this->buildClient()->withHeaders($headers)->get($endpoint, $query);

        return $this->handleResponse($response);
    }

    public function post(string $endpoint, array $data = [], array $headers = []): array
    {
        $response = $this->buildClient()->withHeaders($headers)->post($endpoint, $data);

        return $this->handleResponse($response);
    }

    public function put(string $endpoint, array $data = [], array $headers = []): array
    {
        $response = $this->buildClient()->withHeaders($headers)->put($endpoint, $data);

        return $this->handleResponse($response);
    }

    public function patch(string $endpoint, array $data = [], array $headers = []): array
    {
        $response = $this->buildClient()->withHeaders($headers)->patch($endpoint, $data);

        return $this->handleResponse($response);
    }

    public function delete(string $endpoint, array $headers = []): array
    {
        $response = $this->buildClient()->withHeaders($headers)->delete($endpoint);

        return $this->handleResponse($response);
    }
}
