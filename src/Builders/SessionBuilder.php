<?php

namespace Laraditz\Xendit\Builders;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Laraditz\Xendit\Enums\SessionMode;
use Laraditz\Xendit\Enums\SessionType;
use Laraditz\Xendit\Events\SessionCanceled;
use Laraditz\Xendit\Events\SessionCreated;
use Laraditz\Xendit\Models\XenditSession;
use Laraditz\Xendit\Services\SessionService;

class SessionBuilder extends BaseBuilder
{
    protected SessionService $service;

    public function __construct(SessionService $service)
    {
        $this->service = $service;
    }

    protected function generateExternalId(): string
    {
        return $this->attributes['reference_id']
            ?? $this->attributes['external_id']
            ?? 'XND-SESSION-' . strtoupper(Str::random(12));
    }

    public function referenceId(string $id): static
    {
        $this->attributes['reference_id'] = $id;
        return $this;
    }

    public function country(string $country): static
    {
        $this->attributes['country'] = $country;
        return $this;
    }

    public function sessionType(string|SessionType $type): static
    {
        $this->attributes['session_type'] = $type instanceof SessionType ? $type->value : $type;
        return $this;
    }

    public function mode(string|SessionMode $mode): static
    {
        $this->attributes['mode'] = $mode instanceof SessionMode ? $mode->value : $mode;
        return $this;
    }

    public function successUrl(string $url): static
    {
        $this->attributes['success_return_url'] = $url;
        return $this;
    }

    public function cancelUrl(string $url): static
    {
        $this->attributes['cancel_return_url'] = $url;
        return $this;
    }

    public function locale(string $locale): static
    {
        $this->attributes['locale'] = $locale;
        return $this;
    }

    public function expiresAt(Carbon $dt): static
    {
        $this->attributes['expires_at'] = $dt;
        return $this;
    }

    public function captureMethod(string $method): static
    {
        $this->attributes['capture_method'] = $method;
        return $this;
    }

    public function allowSavePaymentMethod(string $value): static
    {
        $this->attributes['allow_save_payment_method'] = $value;
        return $this;
    }

    public function allowedPaymentChannels(array $channels): static
    {
        $this->attributes['allowed_payment_channels'] = $channels;
        return $this;
    }

    public function customer(array $customer): static
    {
        $this->attributes['customer'] = $customer;
        return $this;
    }

    public function customerId(string $id): static
    {
        $this->attributes['customer_id'] = $id;
        return $this;
    }

    public function items(array $items): static
    {
        $this->attributes['items'] = $items;
        return $this;
    }

    public function create(array $data = []): XenditSession
    {
        $mergeKeys = [
            'reference_id', 'session_type', 'mode', 'amount', 'currency', 'country',
            'description', 'customer_id', 'customer', 'success_return_url', 'cancel_return_url',
            'locale', 'expires_at', 'capture_method', 'allow_save_payment_method',
            'allowed_payment_channels', 'items', 'metadata',
        ];
        foreach ($mergeKeys as $key) {
            if (array_key_exists($key, $data)) {
                $this->attributes[$key] = $data[$key];
            }
        }

        $this->attributes['reference_id'] = $this->generateExternalId();

        $session = XenditSession::create(array_filter([
            'reference_id'       => $this->attributes['reference_id'],
            'session_type'       => $this->attributes['session_type'] ?? null,
            'mode'               => $this->attributes['mode'] ?? null,
            'amount'             => $this->attributes['amount'] ?? null,
            'currency'           => $this->attributes['currency'] ?? null,
            'country'            => $this->attributes['country'] ?? null,
            'description'        => $this->attributes['description'] ?? null,
            'customer_id'        => $this->attributes['customer_id'] ?? null,
            'customer'           => $this->attributes['customer'] ?? null,
            'success_return_url' => $this->attributes['success_return_url'] ?? null,
            'cancel_return_url'  => $this->attributes['cancel_return_url'] ?? null,
            'metadata'           => $this->attributes['metadata'] ?? null,
            'payable_id'         => $this->payable?->getKey(),
            'payable_type'       => $this->payable ? get_class($this->payable) : null,
        ], fn($v) => !is_null($v)));

        $payload = $this->buildApiPayload();

        try {
            $response = $this->service->create($payload);
        } catch (\Exception $e) {
            $session->forceDelete();
            throw $e;
        }

        $session->update(array_filter([
            'payment_session_id' => $response['payment_session_id'] ?? null,
            'payment_link_url'   => $response['payment_link_url'] ?? null,
            'components_sdk_key' => $response['components_sdk_key'] ?? null,
            'payment_id'         => $response['payment_id'] ?? null,
            'payment_token_id'   => $response['payment_token_id'] ?? null,
            'expires_at'         => isset($response['expires_at'])
                ? Carbon::parse($response['expires_at'])
                : null,
            'session_details'    => $response,
        ], fn($v) => !is_null($v)));

        event(new SessionCreated($session->fresh()));

        return $session->fresh();
    }

    public function get(string $id): array
    {
        return $this->service->get($id);
    }

    public function cancel(string $id): array
    {
        $response = $this->service->cancel($id);

        $session = XenditSession::paymentSessionId($id)->first();
        if ($session) {
            $session->markAsCanceled();
            event(new SessionCanceled($session));
        }

        return $response;
    }

    protected function buildApiPayload(): array
    {
        $sessionType = $this->attributes['session_type'] ?? null;

        $payload = array_filter([
            'reference_id'              => $this->attributes['reference_id'],
            'session_type'              => $sessionType,
            'mode'                      => $this->attributes['mode'] ?? null,
            'currency'                  => $this->attributes['currency'] ?? config('xendit.default_currency', 'MYR'),
            'country'                   => $this->attributes['country'] ?? null,
            'amount'                    => $this->attributes['amount'] ?? null,
            'description'               => $this->attributes['description'] ?? null,
            'success_return_url'        => $this->attributes['success_return_url'] ?? null,
            'cancel_return_url'         => $this->attributes['cancel_return_url'] ?? null,
            'locale'                    => $this->attributes['locale'] ?? null,
            'customer_id'               => $this->attributes['customer_id'] ?? null,
            'allow_save_payment_method' => $this->attributes['allow_save_payment_method'] ?? null,
            'allowed_payment_channels'  => $this->attributes['allowed_payment_channels'] ?? null,
            'items'                     => $this->attributes['items'] ?? null,
            'metadata'                  => $this->attributes['metadata'] ?? null,
        ], fn($v) => !is_null($v) && $v !== []);

        if ($sessionType === SessionType::Pay->value) {
            $payload['capture_method'] = $this->attributes['capture_method'] ?? 'AUTOMATIC';
        }

        if (!empty($this->attributes['customer'])) {
            $customer = $this->attributes['customer'];
            if (!isset($customer['type'])) {
                $customer['type'] = 'INDIVIDUAL';
            }
            $payload['customer'] = $customer;
        }

        if (!empty($this->attributes['expires_at'])) {
            $dt = $this->attributes['expires_at'];
            $payload['expires_at'] = $dt instanceof Carbon ? $dt->toIso8601String() : $dt;
        }

        return $payload;
    }
}
