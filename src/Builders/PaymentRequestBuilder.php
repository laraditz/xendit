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
        // $this->attributes['customer'] = $customer;
        if (isset($customer['email'])) {
            $this->attributes['payer_email'] = $customer['email'];
        }

        if (isset($customer['mobile_number'])) {
            $this->attributes['payer_phone'] = $customer['mobile_number'];
        }

        if (isset($customer['given_names'])) {
            $this->attributes['payer_name'] = $customer['given_names'];
        }
        return $this;
    }

    public function customerId(string $customerId): static
    {
        $this->attributes['customer_id'] = $customerId;
        return $this;
    }

    /**
     * Set customer email
     */
    public function email(string $email): static
    {
        $this->attributes['payer_email'] = $email;
        return $this;
    }

    /**
     * Set customer phone
     */
    public function phone(string $phone): static
    {
        $this->attributes['payer_phone'] = $phone;
        return $this;
    }

    /**
     * Set customer given names
     */
    public function givenNames(string $givenNames): static
    {
        $this->attributes['payer_name'] = $givenNames;
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
        $this->paymentMethods = [
            [
                'channel_code' => $channelCode,
            ]
        ];
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

        // dd($this->attributes, $this->getPayableAttributes(), [
        //     'external_id' => $externalId,
        //     'payment_type' => PaymentType::PaymentRequest->value,
        // ]);

        // Create payment record
        $payment = XenditPayment::create(array_merge(
            $this->attributes,
            $this->getPayableAttributes(),
            [
                'external_id' => $externalId,
                'payment_channel' => $this->paymentMethods[0]['channel_code'] ?? null,
                'payment_type' => PaymentType::PaymentRequest->value,
            ]
        ));

        // Call Xendit API to create payment request
        $payload = $this->buildApiPayload($externalId);

        // Debug: Log the payload
        // \Log::info('Xendit Payment Request Payload:', $payload);

        $response = $this->service->create($payload);

        // Debug: Log the response
        // \Log::info('Xendit Payment Request Response:', $response);

        // Extract payment URL from actions (find REDIRECT_CUSTOMER action)
        $paymentUrl = null;
        if (isset($response['actions']) && is_array($response['actions'])) {
            $redirectAction = collect($response['actions'])->firstWhere('type', 'REDIRECT_CUSTOMER');
            $paymentUrl = $redirectAction['value'] ?? null;
        }

        // Update payment with Xendit response (v3 API structure)
        $payment->update([
            'xendit_id' => $response['payment_request_id'] ?? $response['id'] ?? null,
            'payment_channel' => $response['channel_code'] ?? $payment->payment_channel ?? null,
            'payment_url' => $paymentUrl,
            'success_redirect_url' => $response['channel_properties']['success_return_url'] ?? null,
            'failure_redirect_url' => $response['channel_properties']['failure_return_url'] ?? null,
            'expired_at' => isset($response['expires_at']) ? \Carbon\Carbon::parse($response['expires_at']) : null,
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
            'success_redirect_url' => 'success_redirect_url',
            'failure_redirect_url' => 'failure_redirect_url',
            'items' => 'items',
            'channel_properties' => 'channel_properties',
            'country' => 'country',
            'capture_method' => 'capture_method',
        ];

        // Use collection to map and merge attributes (data takes precedence)
        collect($attributeMap)->each(function ($attributeKey, $dataKey) use ($data) {
            if (isset($data[$dataKey])) {
                $this->attributes[$attributeKey] = $data[$dataKey];
            }
        });

        // Handle channel_code - set as payment method
        if (isset($data['channel_code'])) {
            $this->paymentMethods = [
                [
                    'channel_code' => $data['channel_code'],
                ]
            ];

            // Merge channel_properties if provided
            if (isset($data['channel_properties'])) {
                $this->paymentMethods[0]['channel_properties'] = $data['channel_properties'];
            }
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

        if (isset($this->attributes['payer_email'])) {
            $payload['customer']['email'] = $this->attributes['payer_email'];
        }

        if (isset($this->attributes['payer_phone'])) {
            $payload['customer']['mobile_number'] = $this->attributes['payer_phone'];
        }

        if (isset($this->attributes['payer_name'])) {
            $payload['customer']['individual_detail']['given_names'] = $this->attributes['payer_name'];
        }

        if (isset($payload['customer'])) {
            $payload['customer']['type'] = "INDIVIDUAL";
        }

        if ($this->attributes['customer_id'] && isset($payload['customer'])) {
            $payload['customer']['reference_id'] = $this->attributes['customer_id'];
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
