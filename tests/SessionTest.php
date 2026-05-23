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

    public function test_observer_sets_default_status_to_active(): void
    {
        $session = \Laraditz\Xendit\Models\XenditSession::create([
            'reference_id' => 'ref-obs-1',
            'amount'       => 100.00,
            'session_type' => 'PAY',
            'mode'         => 'PAYMENT_LINK',
        ]);

        $this->assertEquals(\Laraditz\Xendit\Enums\SessionStatus::Active, $session->status);
    }

    public function test_observer_sets_default_currency(): void
    {
        $session = \Laraditz\Xendit\Models\XenditSession::create([
            'reference_id' => 'ref-obs-2',
            'amount'       => 50.00,
            'session_type' => 'PAY',
            'mode'         => 'PAYMENT_LINK',
        ]);

        $this->assertEquals('MYR', $session->currency);
    }

    public function test_session_mark_as_completed(): void
    {
        $session = \Laraditz\Xendit\Models\XenditSession::create([
            'reference_id' => 'ref-complete',
            'amount'       => 100.00,
            'session_type' => 'PAY',
            'mode'         => 'PAYMENT_LINK',
        ]);

        $session->markAsCompleted();

        $this->assertEquals(\Laraditz\Xendit\Enums\SessionStatus::Completed, $session->fresh()->status);
        $this->assertNotNull($session->fresh()->completed_at);
    }

    public function test_session_mark_as_expired(): void
    {
        $session = \Laraditz\Xendit\Models\XenditSession::create([
            'reference_id' => 'ref-expire',
            'amount'       => 100.00,
            'session_type' => 'PAY',
            'mode'         => 'PAYMENT_LINK',
            'expires_at'   => now()->addMinutes(30),
        ]);

        $session->markAsExpired();

        $this->assertEquals(\Laraditz\Xendit\Enums\SessionStatus::Expired, $session->fresh()->status);
        $this->assertNotNull($session->fresh()->expires_at);
    }

    public function test_session_mark_as_canceled(): void
    {
        $session = \Laraditz\Xendit\Models\XenditSession::create([
            'reference_id' => 'ref-cancel',
            'amount'       => 100.00,
            'session_type' => 'PAY',
            'mode'         => 'PAYMENT_LINK',
        ]);

        $session->markAsCanceled();

        $this->assertEquals(\Laraditz\Xendit\Enums\SessionStatus::Canceled, $session->fresh()->status);
        $this->assertNotNull($session->fresh()->canceled_at);
    }

    public function test_session_belongs_to_customer(): void
    {
        $customer = \Laraditz\Xendit\Models\XenditCustomer::create([
            'reference_id' => 'cust-rel',
            'xendit_id'    => 'cust_xyz',
        ]);

        $session = \Laraditz\Xendit\Models\XenditSession::create([
            'reference_id' => 'ref-rel',
            'amount'       => 100.00,
            'session_type' => 'PAY',
            'mode'         => 'PAYMENT_LINK',
            'customer_id'  => 'cust_xyz',
        ]);

        $this->assertInstanceOf(\Laraditz\Xendit\Models\XenditCustomer::class, $session->xenditCustomer);
        $this->assertEquals('cust_xyz', $session->xenditCustomer->xendit_id);
    }

    public function test_session_events_hold_session_model(): void
    {
        $session = \Laraditz\Xendit\Models\XenditSession::create([
            'reference_id' => 'ref-events',
            'amount'       => 100.00,
            'session_type' => 'PAY',
            'mode'         => 'PAYMENT_LINK',
        ]);

        $created   = new \Laraditz\Xendit\Events\SessionCreated($session);
        $completed = new \Laraditz\Xendit\Events\SessionCompleted($session);
        $expired   = new \Laraditz\Xendit\Events\SessionExpired($session);
        $canceled  = new \Laraditz\Xendit\Events\SessionCanceled($session);

        $this->assertSame($session, $created->session);
        $this->assertSame($session, $completed->session);
        $this->assertSame($session, $expired->session);
        $this->assertSame($session, $canceled->session);
    }

    public function test_can_create_session_via_builder(): void
    {
        Http::fake([
            'api.xendit.co/sessions' => Http::response([
                'payment_session_id' => 'ps-abc123',
                'payment_link_url'   => 'https://checkout.xendit.co/sessions/ps-abc123',
                'status'             => 'ACTIVE',
                'session_type'       => 'PAY',
                'expires_at'         => '2026-05-12T11:00:00Z',
            ], 201),
        ]);

        $session = \Laraditz\Xendit\Facades\Xendit::session()
            ->referenceId('order-001')
            ->amount(100.00)
            ->currency('MYR')
            ->country('MY')
            ->sessionType('PAY')
            ->mode('PAYMENT_LINK')
            ->successUrl('https://example.com/success')
            ->cancelUrl('https://example.com/cancel')
            ->create();

        $this->assertInstanceOf(\Laraditz\Xendit\Models\XenditSession::class, $session);
        $this->assertEquals('ps-abc123', $session->payment_session_id);
        $this->assertEquals('https://checkout.xendit.co/sessions/ps-abc123', $session->payment_link_url);
        $this->assertEquals('order-001', $session->reference_id);
        $this->assertDatabaseHas('xendit_sessions', [
            'reference_id'       => 'order-001',
            'payment_session_id' => 'ps-abc123',
        ]);
    }

    public function test_create_auto_generates_reference_id_when_not_set(): void
    {
        Http::fake([
            'api.xendit.co/sessions' => Http::response([
                'payment_session_id' => 'ps-auto',
                'payment_link_url'   => 'https://checkout.xendit.co/sessions/ps-auto',
                'expires_at'         => '2026-05-12T11:00:00Z',
            ], 201),
        ]);

        $session = \Laraditz\Xendit\Facades\Xendit::session()
            ->amount(50.00)
            ->sessionType('PAY')
            ->mode('PAYMENT_LINK')
            ->create();

        $this->assertStringStartsWith('XND-SESSION-', $session->reference_id);
    }

    public function test_create_dispatches_session_created_event(): void
    {
        Event::fake();

        Http::fake([
            'api.xendit.co/sessions' => Http::response([
                'payment_session_id' => 'ps-evt',
                'payment_link_url'   => 'https://checkout.xendit.co/sessions/ps-evt',
                'expires_at'         => '2026-05-12T11:00:00Z',
            ], 201),
        ]);

        \Laraditz\Xendit\Facades\Xendit::session()
            ->referenceId('order-evt')
            ->amount(100.00)
            ->sessionType('PAY')
            ->mode('PAYMENT_LINK')
            ->create();

        Event::assertDispatched(\Laraditz\Xendit\Events\SessionCreated::class);
    }

    public function test_create_sends_capture_method_only_for_pay_type(): void
    {
        Http::fake([
            'api.xendit.co/sessions' => Http::response([
                'payment_session_id' => 'ps-cap',
                'expires_at'         => '2026-05-12T11:00:00Z',
            ], 201),
        ]);

        \Laraditz\Xendit\Facades\Xendit::session()
            ->referenceId('order-cap')
            ->amount(100.00)
            ->sessionType('PAY')
            ->mode('PAYMENT_LINK')
            ->create();

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            return isset($body['capture_method']) && $body['capture_method'] === 'AUTOMATIC';
        });
    }

    public function test_create_does_not_send_capture_method_for_save_type(): void
    {
        Http::fake([
            'api.xendit.co/sessions' => Http::response([
                'payment_session_id' => 'ps-save',
                'expires_at'         => '2026-05-12T11:00:00Z',
            ], 201),
        ]);

        \Laraditz\Xendit\Facades\Xendit::session()
            ->referenceId('order-save')
            ->amount(0)
            ->sessionType('SAVE')
            ->mode('PAYMENT_LINK')
            ->create();

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            return !isset($body['capture_method']);
        });
    }

    public function test_create_force_deletes_record_on_api_failure(): void
    {
        Http::fake([
            'api.xendit.co/sessions' => Http::response(['error_code' => 'API_VALIDATION_ERROR'], 400),
        ]);

        try {
            \Laraditz\Xendit\Facades\Xendit::session()
                ->referenceId('order-fail')
                ->amount(100.00)
                ->sessionType('PAY')
                ->mode('PAYMENT_LINK')
                ->create();
        } catch (\Exception $e) {}

        $this->assertDatabaseMissing('xendit_sessions', ['reference_id' => 'order-fail']);
    }

    public function test_create_stores_inline_customer_in_json_column(): void
    {
        Http::fake([
            'api.xendit.co/sessions' => Http::response([
                'payment_session_id' => 'ps-cust',
                'expires_at'         => '2026-05-12T11:00:00Z',
            ], 201),
        ]);

        $session = \Laraditz\Xendit\Facades\Xendit::session()
            ->referenceId('order-cust')
            ->amount(100.00)
            ->sessionType('PAY')
            ->mode('PAYMENT_LINK')
            ->customer(['type' => 'INDIVIDUAL', 'email' => 'john@example.com'])
            ->create();

        $this->assertNotNull($session->customer);
        $this->assertEquals('john@example.com', $session->customer['email']);
    }

    public function test_cancel_updates_session_status_and_dispatches_event(): void
    {
        Event::fake();

        Http::fake([
            'api.xendit.co/sessions/ps-abc123/cancel' => Http::response(['status' => 'CANCELED'], 200),
        ]);

        $session = \Laraditz\Xendit\Models\XenditSession::create([
            'reference_id'       => 'order-cancel-test',
            'payment_session_id' => 'ps-abc123',
            'amount'             => 100.00,
            'session_type'       => 'PAY',
            'mode'               => 'PAYMENT_LINK',
        ]);

        \Laraditz\Xendit\Facades\Xendit::session()->cancel('ps-abc123');

        $this->assertEquals(
            \Laraditz\Xendit\Enums\SessionStatus::Canceled,
            $session->fresh()->status
        );
        Event::assertDispatched(\Laraditz\Xendit\Events\SessionCanceled::class);
    }

    public function test_get_returns_array(): void
    {
        Http::fake([
            'api.xendit.co/sessions/ps-abc123' => Http::response(['payment_session_id' => 'ps-abc123'], 200),
        ]);

        $result = \Laraditz\Xendit\Facades\Xendit::session()->get('ps-abc123');

        $this->assertIsArray($result);
        $this->assertEquals('ps-abc123', $result['payment_session_id']);
    }

    public function test_webhook_handler_marks_session_completed(): void
    {
        Event::fake();

        $session = \Laraditz\Xendit\Models\XenditSession::create([
            'reference_id'       => 'order-webhook-complete',
            'payment_session_id' => 'ps-wh1',
            'amount'             => 100.00,
            'session_type'       => 'PAY',
            'mode'               => 'PAYMENT_LINK',
        ]);

        $handler = app(\Laraditz\Xendit\Support\WebhookHandler::class);
        $handler->handle([
            'event'       => 'payment_session.completed',
            'business_id' => 'biz-123',
            'created'     => now()->toIso8601String(),
            'data'        => [
                'payment_session_id' => 'ps-wh1',
                'reference_id'       => 'order-webhook-complete',
                'status'             => 'COMPLETED',
            ],
        ]);

        $this->assertEquals(
            \Laraditz\Xendit\Enums\SessionStatus::Completed,
            $session->fresh()->status
        );
        Event::assertDispatched(\Laraditz\Xendit\Events\SessionCompleted::class);
    }

    public function test_webhook_handler_marks_session_expired(): void
    {
        Event::fake();

        $session = \Laraditz\Xendit\Models\XenditSession::create([
            'reference_id'       => 'order-webhook-expire',
            'payment_session_id' => 'ps-wh2',
            'amount'             => 100.00,
            'session_type'       => 'PAY',
            'mode'               => 'PAYMENT_LINK',
            'expires_at'         => now()->addMinutes(30),
        ]);

        $handler = app(\Laraditz\Xendit\Support\WebhookHandler::class);
        $handler->handle([
            'event'       => 'payment_session.expired',
            'business_id' => 'biz-123',
            'created'     => now()->toIso8601String(),
            'data'        => [
                'payment_session_id' => 'ps-wh2',
                'reference_id'       => 'order-webhook-expire',
                'status'             => 'EXPIRED',
            ],
        ]);

        $this->assertEquals(
            \Laraditz\Xendit\Enums\SessionStatus::Expired,
            $session->fresh()->status
        );
        $this->assertNotNull($session->fresh()->expires_at);
        Event::assertDispatched(\Laraditz\Xendit\Events\SessionExpired::class);
    }

    public function test_xendit_session_returns_session_builder(): void
    {
        $builder = \Laraditz\Xendit\Facades\Xendit::session();

        $this->assertInstanceOf(\Laraditz\Xendit\Builders\SessionBuilder::class, $builder);
    }
}
