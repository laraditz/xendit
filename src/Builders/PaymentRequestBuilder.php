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
     * Add all e-wallet payment methods
     */
    public function ewallets(): static
    {
        $this->paymentMethod('EWALLET', ['type' => 'ONE_TIME_USE']);
        return $this;
    }

    /**
     * Add virtual account payment method
     */
    public function virtualAccounts(): static
    {
        $this->paymentMethod('VIRTUAL_ACCOUNT', ['type' => 'ONE_TIME_USE']);
        return $this;
    }

    /**
     * Add QR code payment method
     */
    public function qrCode(): static
    {
        $this->paymentMethod('QR_CODE', ['type' => 'ONE_TIME_USE']);
        return $this;
    }

    /**
     * Add over-the-counter payment method
     */
    public function overTheCounter(): static
    {
        $this->paymentMethod('OVER_THE_COUNTER', ['type' => 'ONE_TIME_USE']);
        return $this;
    }

    /**
     * Add direct debit payment method
     */
    public function directDebit(): static
    {
        $this->paymentMethod('DIRECT_DEBIT', ['type' => 'ONE_TIME_USE']);
        return $this;
    }

    /**
     * Add card payment method
     */
    public function card(): static
    {
        $this->paymentMethod('CARD');
        return $this;
    }

    /**
     * Add all available payment methods
     */
    public function allMethods(): static
    {
        return $this
            ->ewallets()
            ->virtualAccounts()
            ->qrCode()
            ->overTheCounter();
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
     */
    public function create(): XenditPayment
    {
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
     * Build API payload for Xendit
     */
    protected function buildApiPayload(string $externalId): array
    {
        $payload = [
            'reference_id' => $externalId,
            'amount' => $this->attributes['amount'],
            'currency' => $this->attributes['currency'] ?? config('xendit.default_currency', 'MYR'),
        ];

        // Add customer information
        if (isset($this->attributes['customer'])) {
            $payload['customer'] = $this->attributes['customer'];
        }

        // Add description
        if (isset($this->attributes['description'])) {
            $payload['description'] = $this->attributes['description'];
        }

        // Add payment methods
        if (!empty($this->paymentMethods)) {
            $payload['payment_method'] = [
                'type' => 'PAYMENT_METHOD_LIST',
                'payment_methods' => $this->paymentMethods,
                'reusability' => 'ONE_TIME_USE',
            ];
        }

        // Add redirect URLs
        if (isset($this->attributes['success_redirect_url'])) {
            $payload['success_redirect_url'] = $this->attributes['success_redirect_url'];
        }

        if (isset($this->attributes['failure_redirect_url'])) {
            $payload['failure_redirect_url'] = $this->attributes['failure_redirect_url'];
        }

        // Add channel properties
        if (isset($this->attributes['channel_properties'])) {
            $payload['channel_properties'] = $this->attributes['channel_properties'];
        }

        // Add items
        if (isset($this->attributes['items'])) {
            $payload['items'] = $this->attributes['items'];
        }

        // Add metadata
        if (isset($this->attributes['metadata'])) {
            $payload['metadata'] = $this->attributes['metadata'];
        }

        return array_filter($payload);
    }
}
