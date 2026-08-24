<?php

namespace Laraditz\Xendit\Tests;

use Illuminate\Support\Facades\Http;
use Laraditz\Xendit\Client\XenditClient;
use Laraditz\Xendit\Models\XenditApiLog;

class LogsApiCallsTest extends TestCase
{
    public function test_get_call_creates_an_api_log_row(): void
    {
        Http::fake(['*' => Http::response(['id' => 'pay_1'], 200)]);

        app(XenditClient::class)->get('/payments/pay_1');

        $log = XenditApiLog::first();

        $this->assertNotNull($log);
        $this->assertSame('GET', $log->method);
        $this->assertSame('/payments/pay_1', $log->endpoint);
        $this->assertSame(200, $log->http_status);
        $this->assertIsInt($log->duration_ms);
        $this->assertGreaterThanOrEqual(0, $log->duration_ms);
    }

    public function test_post_call_creates_an_api_log_row(): void
    {
        Http::fake(['*' => Http::response(['id' => 'rfd_1'], 200)]);

        app(XenditClient::class)->post('/refunds', ['amount' => 50000]);

        $log = XenditApiLog::first();

        $this->assertNotNull($log);
        $this->assertSame('POST', $log->method);
        $this->assertSame('/refunds', $log->endpoint);
        $this->assertSame(200, $log->http_status);
        $this->assertSame(['amount' => 50000], $log->request_payload);
        $this->assertSame(['id' => 'rfd_1'], $log->response_payload);
    }

    public function test_delete_call_logs_empty_array_request_payload(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        app(XenditClient::class)->delete('/customers/cust_1');

        $log = XenditApiLog::first();

        $this->assertNotNull($log);
        $this->assertSame('DELETE', $log->method);
        $this->assertSame([], $log->request_payload);
    }

    public function test_reference_id_resolves_from_id(): void
    {
        Http::fake(['*' => Http::response(['id' => 'rfd-1'], 200)]);

        app(XenditClient::class)->post('/refunds', ['amount' => 1]);

        $this->assertSame('rfd-1', XenditApiLog::first()->reference_id);
    }

    public function test_reference_id_resolves_from_payment_request_id(): void
    {
        Http::fake(['*' => Http::response(['payment_request_id' => 'pr-1'], 200)]);

        app(XenditClient::class)->post('/v3/payment_requests', ['amount' => 1]);

        $this->assertSame('pr-1', XenditApiLog::first()->reference_id);
    }

    public function test_reference_id_resolves_from_payment_session_id(): void
    {
        Http::fake(['*' => Http::response(['payment_session_id' => 'ps-1'], 201)]);

        app(XenditClient::class)->post('/sessions', ['amount' => 1]);

        $this->assertSame('ps-1', XenditApiLog::first()->reference_id);
    }

    public function test_reference_id_resolves_from_transaction_id(): void
    {
        Http::fake(['*' => Http::response(['transaction_id' => 'txn-1'], 200)]);

        app(XenditClient::class)->get('/transactions/txn-1');

        $this->assertSame('txn-1', XenditApiLog::first()->reference_id);
    }

    public function test_reference_id_resolves_from_xendit_id(): void
    {
        Http::fake(['*' => Http::response(['xendit_id' => 'xnd-1'], 200)]);

        app(XenditClient::class)->get('/customers/xnd-1');

        $this->assertSame('xnd-1', XenditApiLog::first()->reference_id);
    }

    public function test_reference_id_is_null_for_list_envelope_response(): void
    {
        Http::fake(['*' => Http::response(['data' => [], 'has_more' => false], 200)]);

        app(XenditClient::class)->get('/transactions');

        $this->assertNull(XenditApiLog::first()->reference_id);
    }
}
