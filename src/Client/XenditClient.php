<?php

namespace Laraditz\Xendit\Client;

use Laraditz\Xendit\Client\Concerns\HandlesAuthentication;
use Laraditz\Xendit\Client\Concerns\HandlesErrors;
use Laraditz\Xendit\Client\Concerns\MakesHttpRequests;
use Laraditz\Xendit\Client\Contracts\ClientInterface;
use Laraditz\Xendit\Events\XenditApiRequesting;
use Laraditz\Xendit\Events\XenditApiResponseReceived;

class XenditClient implements ClientInterface
{
    use HandlesAuthentication;
    use HandlesErrors;
    use MakesHttpRequests;

    public function get(string $endpoint, array $query = [], array $headers = []): array
    {
        event(new XenditApiRequesting('GET', $endpoint, $query, []));

        $response = $this->buildClient()->withHeaders($headers)->get($endpoint, $query);

        $result = $this->handleResponse($response);

        event(new XenditApiResponseReceived('GET', $endpoint, $query, [], $result));

        return $result;
    }

    public function post(string $endpoint, array $data = [], array $headers = []): array
    {
        event(new XenditApiRequesting('POST', $endpoint, [], $data));

        $response = $this->buildClient()->withHeaders($headers)->post($endpoint, $data);

        $result = $this->handleResponse($response);

        event(new XenditApiResponseReceived('POST', $endpoint, [], $data, $result));

        return $result;
    }

    public function put(string $endpoint, array $data = [], array $headers = []): array
    {
        event(new XenditApiRequesting('PUT', $endpoint, [], $data));

        $response = $this->buildClient()->withHeaders($headers)->put($endpoint, $data);

        $result = $this->handleResponse($response);

        event(new XenditApiResponseReceived('PUT', $endpoint, [], $data, $result));

        return $result;
    }

    public function patch(string $endpoint, array $data = [], array $headers = []): array
    {
        event(new XenditApiRequesting('PATCH', $endpoint, [], $data));

        $response = $this->buildClient()->withHeaders($headers)->patch($endpoint, $data);

        $result = $this->handleResponse($response);

        event(new XenditApiResponseReceived('PATCH', $endpoint, [], $data, $result));

        return $result;
    }

    public function delete(string $endpoint, array $headers = []): array
    {
        event(new XenditApiRequesting('DELETE', $endpoint, [], []));

        $response = $this->buildClient()->withHeaders($headers)->delete($endpoint);

        $result = $this->handleResponse($response);

        event(new XenditApiResponseReceived('DELETE', $endpoint, [], [], $result));

        return $result;
    }
}
