<?php

namespace Laraditz\Xendit\Client\Concerns;

use Laraditz\Xendit\Exceptions\AuthenticationException;

trait HandlesAuthentication
{
    protected function getApiKey(): string
    {
        $apiKey = config('xendit.api_key');

        if (empty($apiKey)) {
            throw new AuthenticationException('Xendit API key is not configured. Please set XENDIT_API_KEY in your .env file.');
        }

        return $apiKey;
    }

    protected function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Basic ' . base64_encode($this->getApiKey() . ':'),
        ];
    }
}
