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
}
