<?php

namespace Laraditz\Xendit\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laraditz\Xendit\Enums\SessionMode;
use Laraditz\Xendit\Enums\SessionStatus;
use Laraditz\Xendit\Enums\SessionType;

class SessionTest extends TestCase
{
    public function test_session_status_enum_values(): void
    {
        $this->assertEquals(1, SessionStatus::Active->value);
        $this->assertEquals(2, SessionStatus::Completed->value);
        $this->assertEquals(3, SessionStatus::Expired->value);
        $this->assertEquals(4, SessionStatus::Canceled->value);
    }

    public function test_session_status_is_active(): void
    {
        $this->assertTrue(SessionStatus::Active->isActive());
        $this->assertFalse(SessionStatus::Completed->isActive());
    }

    public function test_session_status_is_final(): void
    {
        $this->assertFalse(SessionStatus::Active->isFinal());
        $this->assertTrue(SessionStatus::Completed->isFinal());
        $this->assertTrue(SessionStatus::Expired->isFinal());
        $this->assertTrue(SessionStatus::Canceled->isFinal());
    }

    public function test_session_type_enum_values(): void
    {
        $this->assertEquals('PAY', SessionType::Pay->value);
        $this->assertEquals('SAVE', SessionType::Save->value);
        $this->assertEquals('SUBSCRIPTION', SessionType::Subscription->value);
    }

    public function test_session_mode_enum_values(): void
    {
        $this->assertEquals('PAYMENT_LINK', SessionMode::PaymentLink->value);
        $this->assertEquals('COMPONENTS', SessionMode::Components->value);
    }

    public function test_xendit_sessions_table_exists(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('xendit_sessions'));
    }

    public function test_xendit_sessions_table_has_expected_columns(): void
    {
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('xendit_sessions');

        foreach ([
            'id', 'reference_id', 'payment_session_id',
            'payable_id', 'payable_type',
            'session_type', 'mode', 'status',
            'amount', 'currency', 'country', 'description',
            'customer_id', 'customer',
            'payment_link_url', 'components_sdk_key',
            'success_return_url', 'cancel_return_url',
            'payment_id', 'payment_token_id',
            'metadata', 'session_details',
            'expires_at', 'completed_at', 'canceled_at',
            'created_at', 'updated_at', 'deleted_at',
        ] as $column) {
            $this->assertContains($column, $columns, "Missing column: $column");
        }
    }
}
