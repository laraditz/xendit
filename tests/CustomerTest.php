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
}
