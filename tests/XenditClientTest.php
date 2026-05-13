<?php

namespace Laraditz\Xendit\Tests;

use Illuminate\Support\Facades\Http;

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
}
