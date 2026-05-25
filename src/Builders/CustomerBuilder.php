<?php

namespace Laraditz\Xendit\Builders;

use Laraditz\Xendit\Enums\CustomerType;
use Laraditz\Xendit\Events\CustomerCreated;
use Laraditz\Xendit\Models\XenditCustomer;
use Laraditz\Xendit\Services\CustomerService;

class CustomerBuilder extends BaseBuilder
{
    protected CustomerService $service;

    public function __construct(CustomerService $service)
    {
        $this->service = $service;
    }

    public function referenceId(string $id): static
    {
        $this->attributes['reference_id'] = $id;
        return $this;
    }

    public function type(string|CustomerType $type): static
    {
        $this->attributes['type'] = $type instanceof CustomerType ? $type->value : $type;
        return $this;
    }

    public function givenNames(string $names): static
    {
        $this->attributes['individual_detail']['given_names'] = $names;
        return $this;
    }

    public function surname(string $name): static
    {
        $this->attributes['individual_detail']['surname'] = $name;
        return $this;
    }

    public function dateOfBirth(string $date): static
    {
        $this->attributes['individual_detail']['date_of_birth'] = $date;
        return $this;
    }

    public function nationality(string $code): static
    {
        $this->attributes['individual_detail']['nationality'] = $code;
        return $this;
    }

    public function gender(string $gender): static
    {
        $this->attributes['individual_detail']['gender'] = $gender;
        return $this;
    }

    public function businessName(string $name): static
    {
        $this->attributes['business_detail']['business_name'] = $name;
        return $this;
    }

    public function tradingName(string $name): static
    {
        $this->attributes['business_detail']['trading_name'] = $name;
        return $this;
    }

    public function email(string $email): static
    {
        $this->attributes['email'] = $email;
        return $this;
    }

    public function mobileNumber(string $number): static
    {
        $this->attributes['mobile_number'] = $number;
        return $this;
    }

    public function phoneNumber(string $number): static
    {
        $this->attributes['phone_number'] = $number;
        return $this;
    }

    public function address(array $address): static
    {
        $this->attributes['addresses'][] = $address;
        return $this;
    }

    public function create(): XenditCustomer
    {
        if (empty($this->attributes['reference_id'])) {
            throw new \InvalidArgumentException('referenceId() is required to create a customer.');
        }

        $type = $this->attributes['type'] ?? CustomerType::Individual->value;

        $customer = XenditCustomer::create(array_filter([
            'reference_id'      => $this->attributes['reference_id'],
            'type'              => $type,
            'email'             => $this->attributes['email'] ?? null,
            'mobile_number'     => $this->attributes['mobile_number'] ?? null,
            'phone_number'      => $this->attributes['phone_number'] ?? null,
            'individual_detail' => $this->attributes['individual_detail'] ?? null,
            'business_detail'   => $this->attributes['business_detail'] ?? null,
            'addresses'         => $this->attributes['addresses'] ?? null,
            'metadata'          => $this->attributes['metadata'] ?? null,
        ], fn($v) => !is_null($v)));

        $payload = $this->buildApiPayload($type);

        try {
            $response = $this->service->create($payload, $this->headers);
        } catch (\Exception $e) {
            $customer->forceDelete();
            throw $e;
        }

        $customer->update([
            'xendit_id'         => $response['id'] ?? null,
            'reference_id'      => $response['reference_id'] ?? $customer->reference_id,
            'email'             => $response['email'] ?? $customer->email,
            'mobile_number'     => $response['mobile_number'] ?? $customer->mobile_number,
            'phone_number'      => $response['phone_number'] ?? $customer->phone_number,
            'individual_detail' => $response['individual_detail'] ?? $customer->individual_detail,
            'business_detail'   => $response['business_detail'] ?? $customer->business_detail,
            'addresses'         => $response['addresses'] ?? $customer->addresses,
            'kyc_documents'     => $response['kyc_documents'] ?? null,
            'identity_accounts' => $response['identity_accounts'] ?? null,
            'metadata'          => $response['metadata'] ?? $customer->metadata,
            'customer_details'  => $response,
        ]);

        event(new CustomerCreated($customer->fresh()));

        return $customer->fresh();
    }

    public function get(string $id): array
    {
        return $this->service->get($id, $this->headers);
    }

    public function list(string $referenceId): array
    {
        return $this->service->list($referenceId, $this->headers);
    }

    public function update(string $id, array $data): array
    {
        return $this->service->update($id, $data, $this->headers);
    }

    protected function buildApiPayload(string $type): array
    {
        $payload = [
            'reference_id' => $this->attributes['reference_id'],
            'type'         => $type,
        ];

        foreach (['email', 'mobile_number', 'phone_number'] as $field) {
            if (!empty($this->attributes[$field])) {
                $payload[$field] = $this->attributes[$field];
            }
        }

        if ($type === CustomerType::Individual->value && !empty($this->attributes['individual_detail'])) {
            $payload['individual_detail'] = $this->attributes['individual_detail'];
        }

        if ($type === CustomerType::Business->value && !empty($this->attributes['business_detail'])) {
            $payload['business_detail'] = $this->attributes['business_detail'];
        }

        if (!empty($this->attributes['addresses'])) {
            $payload['addresses'] = $this->attributes['addresses'];
        }

        if (!empty($this->attributes['metadata'])) {
            $payload['metadata'] = $this->attributes['metadata'];
        }

        return $payload;
    }
}
