<?php

namespace Laraditz\Xendit\Client\Contracts;

interface ClientInterface
{
    public function get(string $endpoint, array $query = []): array;

    public function post(string $endpoint, array $data = [], array $headers = []): array;

    public function put(string $endpoint, array $data = []): array;

    public function patch(string $endpoint, array $data = []): array;

    public function delete(string $endpoint): array;
}
