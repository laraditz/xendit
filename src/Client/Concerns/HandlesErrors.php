<?php

namespace Laraditz\Xendit\Client\Concerns;

use Illuminate\Http\Client\Response;
use Laraditz\Xendit\Exceptions\ApiException;
use Laraditz\Xendit\Exceptions\AuthenticationException;
use Laraditz\Xendit\Exceptions\ValidationException;

trait HandlesErrors
{
    protected function handleResponse(Response $response): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $this->throwException($response);
    }

    protected function throwException(Response $response): void
    {
        $statusCode = $response->status();
        $body = $response->json() ?? [];
        $message = $body['message'] ?? $body['error_message'] ?? 'An error occurred';

        match (true) {
            $statusCode === 401 => throw new AuthenticationException($message, $statusCode),
            $statusCode === 400 => throw new ValidationException($message, $body['errors'] ?? [], $statusCode),
            $statusCode >= 400 && $statusCode < 500 => throw new ApiException($message, $statusCode, $body),
            $statusCode >= 500 => throw new ApiException('Xendit server error: ' . $message, $statusCode, $body),
            default => throw new ApiException($message, $statusCode, $body),
        };
    }
}
