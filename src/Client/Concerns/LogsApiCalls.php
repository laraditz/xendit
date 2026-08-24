<?php

namespace Laraditz\Xendit\Client\Concerns;

use Illuminate\Http\Client\Response;
use Laraditz\Xendit\Models\XenditApiLog;

trait LogsApiCalls
{
    protected function logApiCall(string $method, string $endpoint, array $query, array $payload, ?Response $response, float $startedAt): void
    {
        if (!config('xendit.log_api_calls', true)) {
            return;
        }

        $responsePayload = $response?->json();

        XenditApiLog::create([
            'method' => $method,
            'endpoint' => $endpoint,
            'reference_id' => $this->resolveReferenceId($responsePayload),
            'request_payload' => $query ?: $payload,
            'response_payload' => $responsePayload,
            'http_status' => $response?->status(),
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
        ]);
    }

    protected function resolveReferenceId(?array $responsePayload): ?string
    {
        if (!$responsePayload) {
            return null;
        }

        return $responsePayload['id']
            ?? $responsePayload['payment_request_id']
            ?? $responsePayload['payment_session_id']
            ?? $responsePayload['transaction_id']
            ?? $responsePayload['xendit_id']
            ?? null;
    }
}
