<?php

namespace Laraditz\Xendit\Tests;

use Illuminate\Support\Facades\Http;
use Laraditz\Xendit\Facades\Xendit;
use Laraditz\Xendit\Models\XenditPayment;
use Laraditz\Xendit\Models\XenditSession;

class HeaderParametersTest extends TestCase
{
    private function fakeSessionCreate(): void
    {
        Http::fake([
            'api.xendit.co/sessions' => Http::response([
                'payment_session_id' => 'ps-hdr-test',
                'payment_link_url'   => 'https://checkout.xendit.co/hdr',
                'status'             => 'ACTIVE',
                'expires_at'         => '2026-12-31T23:59:59Z',
            ], 201),
        ]);
    }

    private function fakeSessionGet(string $id = 'ps-hdr-test'): void
    {
        Http::fake([
            "api.xendit.co/sessions/{$id}" => Http::response([
                'payment_session_id' => $id,
                'status'             => 'ACTIVE',
            ], 200),
        ]);
    }

    private function fakeSessionCancel(string $id = 'ps-hdr-test'): void
    {
        Http::fake([
            "api.xendit.co/sessions/{$id}/cancel" => Http::response([
                'payment_session_id' => $id,
                'status'             => 'CANCELED',
            ], 200),
        ]);
    }

    private function fakePaymentRequestCreate(): void
    {
        Http::fake([
            'api.xendit.co/v3/payment_requests' => Http::response([
                'payment_request_id' => 'pr-hdr-test',
                'channel_code'       => 'SHOPEEPAY',
                'status'             => 'PENDING',
                'actions'            => [
                    ['type' => 'REDIRECT_CUSTOMER', 'value' => 'https://pay.xendit.co/hdr'],
                ],
            ], 201),
        ]);
    }

    private function fakeCustomerList(): void
    {
        Http::fake([
            'api.xendit.co/customers*' => Http::response(['data' => [], 'has_more' => false], 200),
        ]);
    }

    private function fakeCustomerCreate(): void
    {
        Http::fake([
            'api.xendit.co/customers' => Http::response([
                'id'           => 'cust_hdr1',
                'reference_id' => 'u-hdr-1',
                'type'         => 'INDIVIDUAL',
            ], 201),
        ]);
    }

    // -------------------------------------------------------------------------
    // Generic withHeader / withHeaders on SessionBuilder
    // -------------------------------------------------------------------------

    public function test_with_header_sets_single_header_on_session_create(): void
    {
        $this->fakeSessionCreate();

        Xendit::session()
            ->withHeader('for-user-id', 'uid-single')
            ->amount(100)
            ->sessionType('PAY')
            ->mode('PAYMENT_LINK')
            ->create();

        Http::assertSent(fn($r) =>
            str_contains($r->url(), '/sessions') &&
            $r->hasHeader('for-user-id', 'uid-single')
        );
    }

    public function test_with_headers_merges_multiple_headers(): void
    {
        $this->fakeSessionCreate();

        Xendit::session()
            ->withHeaders([
                'for-user-id'     => 'uid-multi',
                'with-split-rule' => 'rule-multi',
            ])
            ->amount(100)
            ->sessionType('PAY')
            ->mode('PAYMENT_LINK')
            ->create();

        Http::assertSent(fn($r) =>
            str_contains($r->url(), '/sessions') &&
            $r->hasHeader('for-user-id', 'uid-multi') &&
            $r->hasHeader('with-split-rule', 'rule-multi')
        );
    }

    public function test_with_headers_empty_array_is_no_op(): void
    {
        $this->fakeSessionCreate();

        $session = Xendit::session()
            ->withHeaders([])
            ->amount(100)
            ->sessionType('PAY')
            ->mode('PAYMENT_LINK')
            ->create();

        $this->assertInstanceOf(XenditSession::class, $session);
        Http::assertSent(fn($r) => str_contains($r->url(), '/sessions'));
    }

    public function test_generic_with_header_on_session_get(): void
    {
        $this->fakeSessionGet('ps-get-hdr');

        Xendit::session()
            ->withHeader('for-user-id', 'uid-get')
            ->get('ps-get-hdr');

        Http::assertSent(fn($r) =>
            str_contains($r->url(), '/sessions/ps-get-hdr') &&
            $r->hasHeader('for-user-id', 'uid-get')
        );
    }

    public function test_generic_with_header_on_session_cancel(): void
    {
        XenditSession::create([
            'reference_id'       => 'ref-cancel-hdr',
            'payment_session_id' => 'ps-cancel-hdr',
        ]);

        $this->fakeSessionCancel('ps-cancel-hdr');

        Xendit::session()
            ->withHeader('for-user-id', 'uid-cancel')
            ->cancel('ps-cancel-hdr');

        Http::assertSent(fn($r) =>
            str_contains($r->url(), '/sessions/ps-cancel-hdr/cancel') &&
            $r->hasHeader('for-user-id', 'uid-cancel')
        );
    }

    // -------------------------------------------------------------------------
    // Named shortcuts on SessionBuilder — header + DB persistence
    // -------------------------------------------------------------------------

    public function test_for_user_id_sets_correct_header_on_session_create(): void
    {
        $this->fakeSessionCreate();

        Xendit::session()
            ->forUserId('uid-named')
            ->amount(100)
            ->sessionType('PAY')
            ->mode('PAYMENT_LINK')
            ->create();

        Http::assertSent(fn($r) =>
            str_contains($r->url(), '/sessions') &&
            $r->hasHeader('for-user-id', 'uid-named')
        );
    }

    public function test_with_split_rule_sets_correct_header_on_session_create(): void
    {
        $this->fakeSessionCreate();

        Xendit::session()
            ->withSplitRule('rule-named')
            ->amount(100)
            ->sessionType('PAY')
            ->mode('PAYMENT_LINK')
            ->create();

        Http::assertSent(fn($r) =>
            str_contains($r->url(), '/sessions') &&
            $r->hasHeader('with-split-rule', 'rule-named')
        );
    }

    public function test_for_user_id_persisted_to_xendit_sessions(): void
    {
        $this->fakeSessionCreate();

        Xendit::session()
            ->forUserId('uid-persist')
            ->amount(100)
            ->sessionType('PAY')
            ->mode('PAYMENT_LINK')
            ->create();

        $this->assertDatabaseHas('xendit_sessions', ['for_user_id' => 'uid-persist']);
    }

    public function test_split_rule_id_persisted_to_xendit_sessions(): void
    {
        $this->fakeSessionCreate();

        Xendit::session()
            ->withSplitRule('rule-persist')
            ->amount(100)
            ->sessionType('PAY')
            ->mode('PAYMENT_LINK')
            ->create();

        $this->assertDatabaseHas('xendit_sessions', ['split_rule_id' => 'rule-persist']);
    }

    // -------------------------------------------------------------------------
    // Named shortcuts on PaymentRequestBuilder — header + DB persistence
    // -------------------------------------------------------------------------

    public function test_for_user_id_on_payment_request_create(): void
    {
        $this->fakePaymentRequestCreate();

        Xendit::paymentRequest()
            ->forUserId('uid-pr')
            ->amount(100)
            ->ewallets('SHOPEEPAY')
            ->create();

        Http::assertSent(fn($r) =>
            str_contains($r->url(), '/v3/payment_requests') &&
            $r->hasHeader('for-user-id', 'uid-pr')
        );
    }

    public function test_with_split_rule_on_payment_request_create(): void
    {
        $this->fakePaymentRequestCreate();

        Xendit::paymentRequest()
            ->withSplitRule('rule-pr')
            ->amount(100)
            ->ewallets('SHOPEEPAY')
            ->create();

        Http::assertSent(fn($r) =>
            str_contains($r->url(), '/v3/payment_requests') &&
            $r->hasHeader('with-split-rule', 'rule-pr')
        );
    }

    public function test_for_user_id_persisted_to_xendit_payments(): void
    {
        $this->fakePaymentRequestCreate();

        Xendit::paymentRequest()
            ->forUserId('uid-pr-persist')
            ->amount(100)
            ->ewallets('SHOPEEPAY')
            ->create();

        $this->assertDatabaseHas('xendit_payments', ['for_user_id' => 'uid-pr-persist']);
    }

    public function test_split_rule_id_persisted_to_xendit_payments(): void
    {
        $this->fakePaymentRequestCreate();

        Xendit::paymentRequest()
            ->withSplitRule('rule-pr-persist')
            ->amount(100)
            ->ewallets('SHOPEEPAY')
            ->create();

        $this->assertDatabaseHas('xendit_payments', ['split_rule_id' => 'rule-pr-persist']);
    }

    // -------------------------------------------------------------------------
    // CustomerBuilder — header threading
    // -------------------------------------------------------------------------

    public function test_customer_builder_passes_headers_on_list(): void
    {
        $this->fakeCustomerList();

        Xendit::customer()
            ->withHeader('for-user-id', 'uid-list')
            ->list('ref-1');

        Http::assertSent(fn($r) =>
            str_contains($r->url(), '/customers') &&
            $r->hasHeader('for-user-id', 'uid-list')
        );
    }

    public function test_caller_header_overrides_service_idempotency_key(): void
    {
        $this->fakeCustomerCreate();

        Xendit::customer()
            ->withHeader('idempotency-key', 'my-custom-key')
            ->referenceId('u-hdr-1')
            ->givenNames('Test')
            ->create();

        Http::assertSent(fn($r) =>
            str_contains($r->url(), '/customers') &&
            $r->hasHeader('idempotency-key', 'my-custom-key')
        );
    }

    // -------------------------------------------------------------------------
    // Regression — no headers set behaves as before
    // -------------------------------------------------------------------------

    public function test_existing_session_create_unaffected(): void
    {
        $this->fakeSessionCreate();

        $session = Xendit::session()
            ->amount(100)
            ->sessionType('PAY')
            ->mode('PAYMENT_LINK')
            ->create();

        $this->assertInstanceOf(XenditSession::class, $session);
        $this->assertEquals('ps-hdr-test', $session->payment_session_id);
    }
}
