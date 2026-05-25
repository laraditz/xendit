<?php

namespace Laraditz\Xendit\Client\Concerns;

use Illuminate\Support\Facades\Http;

trait MakesHttpRequests
{
    protected function buildClient()
    {
        return Http::withHeaders(array_merge(
            $this->getAuthHeaders(),
            [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'api-version' => config('xendit.api_version', '2024-11-11'),
            ]
        ))
        ->baseUrl($this->getBaseUrl())
        ->timeout(config('xendit.timeout', 30));
    }

    protected function getBaseUrl(): string
    {
        return config('xendit.base_url', 'https://api.xendit.co');
    }
}
