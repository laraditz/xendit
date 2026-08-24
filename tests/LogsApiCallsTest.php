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
}
