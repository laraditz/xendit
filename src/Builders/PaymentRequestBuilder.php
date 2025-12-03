<?php

namespace Laraditz\Xendit\Builders;

use Laraditz\Xendit\Enums\PaymentType;
use Laraditz\Xendit\Models\XenditPayment;
use Laraditz\Xendit\Services\PaymentRequestService;

class PaymentRequestBuilder extends BaseBuilder
{
    protected PaymentRequestService $service;
    protected array $paymentMethods = [];

    public function __construct(PaymentRequestService $service)
    {
        $this->service = $service;
    }

    /**
     * Set customer information
     */
    public function customer(array $customer): static
    {
        $this->attributes['customer'] = $customer;
        return $this;
    }

    /**
     * Set customer email
     */
    public function email(string $email): static
    {
        $this->attributes['customer']['email'] = $email;
        return $this;
    }

    /**
     * Set customer phone
     */
    public function phone(string $phone): static
    {
        $this->attributes['customer']['mobile_number'] = $phone;
        return $this;
    }

    /**
     * Set customer given names
     */
    public function givenNames(string $givenNames): static
    {
        $this->attributes['customer']['given_names'] = $givenNames;
        return $this;
    }

    /**
     * Set success redirect URL
     */
    public function successUrl(string $url): static
    {
        $this->attributes['success_redirect_url'] = $url;
        return $this;
    }

    /**
     * Set failure redirect URL
     */
    public function failureUrl(string $url): static
    {
        $this->attributes['failure_redirect_url'] = $url;
        return $this;
    }

    /**
     * Add payment method
     */
    public function paymentMethod(string $type, array $reusability = null): static
    {
        $method = ['type' => $type];

        if ($reusability) {
            $method['reusability'] = $reusability;
        }

        $this->paymentMethods[] = $method;
        return $this;
    }

    /**
     * Add multiple payment methods
     */
    public function paymentMethods(array $methods): static
    {
        $this->paymentMethods = $methods;
        return $this;
    }

    /**
     * Add e-wallet payment method (ShopeePay)
     */
    public function ewallets(string $channelCode = 'SHOPEEPAY'): static
    {
        $this->paymentMethods[] = [
            'channel_code' => $channelCode,
        ];
        return $this;
    }

    /**
     * Add virtual account payment method
     */
    public function virtualAccounts(string $channelCode = 'BCA'): static
    {
        $this->paymentMethods[] = [
            'channel_code' => $channelCode,
        ];
        return $this;
    }

    /**
     * Add QR code payment method
     */
    public function qrCode(string $channelCode = 'QRIS'): static
    {
        $this->paymentMethods[] = [
            'channel_code' => $channelCode,
        ];
        return $this;
    }

    /**
     * Add over-the-counter payment method
     */
    public function overTheCounter(string $channelCode = 'ALFAMART'): static
    {
        $this->paymentMethods[] = [
            'channel_code' => $channelCode,
        ];
        return $this;
    }

    /**
     * Add direct debit payment method
     */
    public function directDebit(string $channelCode = 'BCA_KLIKPAY'): static
    {
        $this->paymentMethods[] = [
            'channel_code' => $channelCode,
        ];
        return $this;
    }

    /**
     * Add card payment method
     */
    public function card(): static
    {
        $this->paymentMethods[] = [
            'channel_code' => 'CARDS',
        ];
        return $this;
    }

    /**
     * Set specific channel code
     */
    public function channelCode(string $channelCode): static
    {
        $this->paymentMethods = [[
            'channel_code' => $channelCode,
        ]];
        return $this;
    }

    /**
     * Add all available payment methods
     * Note: Can only use one payment method at a time with current API
     */
    public function allMethods(): static
    {
        // Default to SHOPEEPAY for Malaysia
        return $this->ewallets('SHOPEEPAY');
    }

    /**
     * Set payment method options for specific channel
     */
    public function channelProperties(string $channelCode, array $properties): static
    {
        $this->attributes['channel_properties'][$channelCode] = $properties;
        return $this;
    }

    /**
     * Set items/line items
     */
    public function items(array $items): static
    {
        $this->attributes['items'] = $items;
        return $this;
    }

    /**
     * Create the payment request
     * Supports both fluent methods and array parameter
     */
    public function create(array $data = []): XenditPayment
    {
        // If data is provided, it takes precedence over fluent methods
        if (!empty($data)) {
            $this->mergeDataAttributes($data);
        }

        $externalId = $this->generateExternalId();

        // Create payment record
        $payment = XenditPayment::create(array_merge(
            $this->attributes,
            $this->getPayableAttributes(),
            [
                'external_id' => $externalId,
                'payment_type' => PaymentType::PaymentRequest->value,
            ]
        ));

        // Call Xendit API to create payment request
        $response = $this->service->create($this->buildApiPayload($externalId));

        // Update payment with Xendit response
        $payment->update([
            'xendit_id' => $response['id'],
            'payment_url' => $response['actions'][0]['url'] ?? null,
            'payment_details' => $response,
        ]);

        return $payment->fresh();
    }

    /**
     * Merge array data with builder attributes (data takes precedence)
     */
    protected function mergeDataAttributes(array $data): void
    {
        // Map of data keys to attribute keys
        $attributeMap = [
            'reference_id' => 'external_id',
            'amount' => 'amount',
            'currency' => 'currency',
            'description' => 'description',
            'metadata' => 'metadata',
            'customer' => 'customer',
            'success_redirect_url' => 'success_redirect_url',
            'failure_redirect_url' => 'failure_redirect_url',
            'items' => 'items',
            'channel_properties' => 'channel_properties',
        ];

        // Use collection to map and merge attributes (data takes precedence)
        collect($attributeMap)->each(function ($attributeKey, $dataKey) use ($data) {
            if (isset($data[$dataKey])) {
                $this->attributes[$attributeKey] = $data[$dataKey];
            }
        });

        // Handle payment methods separately
        if (isset($data['payment_method'])) {
            $paymentMethod = $data['payment_method'];

            // Extract payment methods array from different structures
            $this->paymentMethods = match (true) {
                isset($paymentMethod['payment_methods']) => $paymentMethod['payment_methods'],
                is_array($paymentMethod) && isset($paymentMethod[0]) => $paymentMethod,
                isset($paymentMethod['type']) => [$paymentMethod],
                default => $this->paymentMethods,
            };
        }
    }

    /**
     * Build API payload for Xendit
     */
    protected function buildApiPayload(string $externalId): array
    {
        $payload = [
            'reference_id' => $externalId,
            'type' => 'PAY',
            'country' => $this->attributes['country'] ?? $this->getCountryFromCurrency(),
            'currency' => $this->attributes['currency'] ?? config('xendit.default_currency', 'MYR'),
            'request_amount' => $this->attributes['amount'],
            'capture_method' => $this->attributes['capture_method'] ?? 'AUTOMATIC',
        ];

        // Add description
        if (isset($this->attributes['description'])) {
            $payload['description'] = $this->attributes['description'];
        }

        // Add channel code (if single payment method)
        if (count($this->paymentMethods) === 1) {
            $method = $this->paymentMethods[0];

            if (isset($method['channel_code'])) {
                $payload['channel_code'] = $method['channel_code'];
            }

            if (isset($method['channel_properties'])) {
                $payload['channel_properties'] = $method['channel_properties'];
            }
        }

        // Add redirect URLs to channel properties
        $channelProperties = $payload['channel_properties'] ?? [];

        if (isset($this->attributes['success_redirect_url'])) {
            $channelProperties['success_return_url'] = $this->attributes['success_redirect_url'];
        }

        if (isset($this->attributes['failure_redirect_url'])) {
            $channelProperties['failure_return_url'] = $this->attributes['failure_redirect_url'];
        }

        if (!empty($channelProperties)) {
            $payload['channel_properties'] = $channelProperties;
        }

        // Add metadata
        if (isset($this->attributes['metadata'])) {
            $payload['metadata'] = $this->attributes['metadata'];
        }

        return array_filter($payload);
    }

    /**
     * Get country code from currency
     */
    protected function getCountryFromCurrency(): string
    {
        return match ($this->attributes['currency'] ?? config('xendit.default_currency', 'MYR')) {
            'IDR' => 'ID',
            'PHP' => 'PH',
            'THB' => 'TH',
            'VND' => 'VN',
            'MYR' => 'MY',
            default => 'ID',
        };
    }
}
