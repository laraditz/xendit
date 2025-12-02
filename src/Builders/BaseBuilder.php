<?php

namespace Laraditz\Xendit\Builders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class BaseBuilder
{
    protected array $attributes = [];
    protected ?Model $payable = null;

    /**
     * Set the amount for payment
     */
    public function amount(float $amount): static
    {
        $this->attributes['amount'] = $amount;
        return $this;
    }

    /**
     * Set the external ID
     */
    public function externalId(string $externalId): static
    {
        $this->attributes['external_id'] = $externalId;
        return $this;
    }

    /**
     * Set the description
     */
    public function description(string $description): static
    {
        $this->attributes['description'] = $description;
        return $this;
    }

    /**
     * Set the currency
     */
    public function currency(string $currency): static
    {
        $this->attributes['currency'] = $currency;
        return $this;
    }

    /**
     * Set custom metadata
     */
    public function metadata(array $metadata): static
    {
        $this->attributes['metadata'] = $metadata;
        return $this;
    }

    /**
     * Attach payment to a model (polymorphic)
     */
    public function for(?Model $payable): static
    {
        $this->payable = $payable;
        return $this;
    }

    /**
     * Generate external ID if not provided
     */
    protected function generateExternalId(): string
    {
        return $this->attributes['external_id'] ?? 'XND-' . strtoupper(Str::random(16));
    }

    /**
     * Get payable attributes for polymorphic relationship
     */
    protected function getPayableAttributes(): array
    {
        if (!$this->payable) {
            return [
                'payable_id' => null,
                'payable_type' => null,
            ];
        }

        return [
            'payable_id' => $this->payable->getKey(),
            'payable_type' => get_class($this->payable),
        ];
    }

    /**
     * Abstract method to create payment
     */
    abstract public function create();
}
