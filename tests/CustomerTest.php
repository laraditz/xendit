<?php

namespace Laraditz\Xendit\Tests;

use Illuminate\Support\Facades\Http;
use Laraditz\Xendit\Enums\CustomerType;

class CustomerTest extends TestCase
{
    public function test_customer_type_enum_values(): void
    {
        $this->assertEquals('INDIVIDUAL', CustomerType::Individual->value);
        $this->assertEquals('BUSINESS', CustomerType::Business->value);
    }

    public function test_xendit_customers_table_exists(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasTable('xendit_customers')
        );
    }

    public function test_xendit_customers_table_has_expected_columns(): void
    {
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('xendit_customers');

        foreach ([
            'id', 'reference_id', 'xendit_id', 'type',
            'email', 'mobile_number', 'phone_number',
            'individual_detail', 'business_detail',
            'addresses', 'kyc_documents', 'identity_accounts',
            'metadata', 'customer_details',
            'created_at', 'updated_at', 'deleted_at',
        ] as $column) {
            $this->assertContains($column, $columns, "Missing column: $column");
        }
    }

    public function test_observer_sets_default_type_to_individual(): void
    {
        $customer = \Laraditz\Xendit\Models\XenditCustomer::create([
            'reference_id' => 'user-defaults',
        ]);

        $this->assertEquals(\Laraditz\Xendit\Enums\CustomerType::Individual, $customer->type);
    }

    public function test_customer_model_casts_type_to_enum(): void
    {
        $customer = \Laraditz\Xendit\Models\XenditCustomer::create([
            'reference_id' => 'user-cast',
            'type'         => 'BUSINESS',
        ]);

        $this->assertInstanceOf(\Laraditz\Xendit\Enums\CustomerType::class, $customer->fresh()->type);
        $this->assertEquals(\Laraditz\Xendit\Enums\CustomerType::Business, $customer->fresh()->type);
    }

    public function test_customer_created_event_holds_model(): void
    {
        $customer = \Laraditz\Xendit\Models\XenditCustomer::create(['reference_id' => 'user-event']);
        $event = new \Laraditz\Xendit\Events\CustomerCreated($customer);

        $this->assertSame($customer, $event->customer);
    }

    public function test_customer_service_create_posts_to_customers_endpoint(): void
    {
        Http::fake([
            'api.xendit.co/customers' => Http::response(['id' => 'cust_srv1', 'reference_id' => 'u1', 'type' => 'INDIVIDUAL'], 201),
        ]);

        $service = app(\Laraditz\Xendit\Services\CustomerService::class);
        $result = $service->create(['reference_id' => 'u1', 'type' => 'INDIVIDUAL']);

        $this->assertEquals('cust_srv1', $result['id']);
        Http::assertSent(fn($r) => str_contains($r->url(), '/customers')
            && $r->hasHeader('idempotency-key', 'u1')
        );
    }

    public function test_customer_service_get_calls_correct_endpoint(): void
    {
        Http::fake([
            'api.xendit.co/customers/cust_abc' => Http::response(['id' => 'cust_abc'], 200),
        ]);

        $service = app(\Laraditz\Xendit\Services\CustomerService::class);
        $result = $service->get('cust_abc');

        $this->assertEquals('cust_abc', $result['id']);
    }

    public function test_customer_service_list_passes_reference_id(): void
    {
        Http::fake([
            'api.xendit.co/customers*' => Http::response(['data' => [], 'has_more' => false], 200),
        ]);

        $service = app(\Laraditz\Xendit\Services\CustomerService::class);
        $service->list('u1');

        Http::assertSent(fn($r) => str_contains($r->url(), 'reference_id=u1'));
    }

    public function test_customer_service_update_patches_correct_endpoint(): void
    {
        Http::fake([
            'api.xendit.co/customers/cust_abc' => Http::response(['id' => 'cust_abc', 'email' => 'new@test.com'], 200),
        ]);

        $service = app(\Laraditz\Xendit\Services\CustomerService::class);
        $result = $service->update('cust_abc', ['email' => 'new@test.com']);

        $this->assertEquals('new@test.com', $result['email']);
    }
}
