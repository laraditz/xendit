<?php

namespace Laraditz\Xendit\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laraditz\Xendit\Events\XenditApiRequesting;
use Laraditz\Xendit\Events\XenditApiResponseReceived;

class XenditClientTest extends TestCase
{
    public function test_post_sends_custom_headers(): void
    {
        Http::fake([
            'api.xendit.co/customers' => Http::response(['id' => 'cust_123'], 201),
        ]);

        $client = app(\Laraditz\Xendit\Client\XenditClient::class);
        $client->post('/customers', ['reference_id' => 'u1'], ['idempotency-key' => 'u1']);

        Http::assertSent(function ($request) {
            return $request->hasHeader('idempotency-key', 'u1');
        });
    }

    public function test_get_sends_custom_headers(): void
    {
        Http::fake([
            'api.xendit.co/payments/*' => Http::response(['id' => 'pay_1'], 200),
        ]);

        $client = app(\Laraditz\Xendit\Client\XenditClient::class);
        $client->get('/payments/pay_1', [], ['for-user-id' => 'uid-get']);

        Http::assertSent(function ($request) {
            return $request->hasHeader('for-user-id', 'uid-get');
        });
    }

    public function test_put_sends_custom_headers(): void
    {
        Http::fake([
            'api.xendit.co/payments/*' => Http::response(['id' => 'pay_1'], 200),
        ]);

        $client = app(\Laraditz\Xendit\Client\XenditClient::class);
        $client->put('/payments/pay_1', ['amount' => 100], ['for-user-id' => 'uid-put']);

        Http::assertSent(function ($request) {
            return $request->hasHeader('for-user-id', 'uid-put');
        });
    }

    public function test_patch_sends_custom_headers(): void
    {
        Http::fake([
            'api.xendit.co/customers/*' => Http::response(['id' => 'cust_1'], 200),
        ]);

        $client = app(\Laraditz\Xendit\Client\XenditClient::class);
        $client->patch('/customers/cust_1', ['email' => 'new@example.com'], ['for-user-id' => 'uid-patch']);

        Http::assertSent(function ($request) {
            return $request->hasHeader('for-user-id', 'uid-patch');
        });
    }

    public function test_delete_sends_custom_headers(): void
    {
        Http::fake([
            'api.xendit.co/customers/*' => Http::response([], 200),
        ]);

        $client = app(\Laraditz\Xendit\Client\XenditClient::class);
        $client->delete('/customers/cust_1', ['for-user-id' => 'uid-delete']);

        Http::assertSent(function ($request) {
            return $request->hasHeader('for-user-id', 'uid-delete');
        });
    }

    public function test_get_dispatches_requesting_and_response_received_events(): void
    {
        Event::fake([XenditApiRequesting::class, XenditApiResponseReceived::class]);

        Http::fake([
            'api.xendit.co/transactions/txn_1' => Http::response(['id' => 'txn_1', 'status' => 'SUCCESS'], 200),
        ]);

        $client = app(\Laraditz\Xendit\Client\XenditClient::class);
        $client->get('/transactions/txn_1');

        Event::assertDispatched(XenditApiRequesting::class, function ($event) {
            return $event->method === 'GET' && $event->endpoint === '/transactions/txn_1';
        });

        Event::assertDispatched(XenditApiResponseReceived::class, function ($event) {
            return $event->method === 'GET'
                && $event->endpoint === '/transactions/txn_1'
                && $event->response === ['id' => 'txn_1', 'status' => 'SUCCESS'];
        });
    }

    public function test_response_received_event_is_not_dispatched_on_error(): void
    {
        Event::fake([XenditApiResponseReceived::class]);

        Http::fake([
            'api.xendit.co/transactions/txn_missing' => Http::response(['message' => 'Not Found'], 404),
        ]);

        $client = app(\Laraditz\Xendit\Client\XenditClient::class);

        try {
            $client->get('/transactions/txn_missing');
        } catch (\Throwable $e) {
            // expected
        }

        Event::assertNotDispatched(XenditApiResponseReceived::class);
    }
}
