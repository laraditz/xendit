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

    public function get(string $endpoint, array $query = []): array
    {
        $response = $this->buildClient()->get($endpoint, $query);

        return $this->handleResponse($response);
    }

    public function post(string $endpoint, array $data = []): array
    {
        $response = $this->buildClient()->post($endpoint, $data);

        return $this->handleResponse($response);
    }

    public function put(string $endpoint, array $data = []): array
    {
        $response = $this->buildClient()->put($endpoint, $data);

        return $this->handleResponse($response);
    }

    public function patch(string $endpoint, array $data = []): array
    {
        $response = $this->buildClient()->patch($endpoint, $data);

        return $this->handleResponse($response);
    }

    public function delete(string $endpoint): array
    {
        $response = $this->buildClient()->delete($endpoint);

        return $this->handleResponse($response);
    }
}
