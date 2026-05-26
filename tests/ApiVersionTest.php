<?php

namespace Laraditz\Xendit\Tests;

use Illuminate\Support\Facades\Http;
use Laraditz\Xendit\Client\Concerns\HasApiVersion;
use Laraditz\Xendit\Client\XenditClient;

class ApiVersionTest extends TestCase
{
    // Scenario 1: no version set anywhere → no api-version header sent
    public function test_no_version_set_sends_no_api_version_header(): void
    {
        Http::fake(['api.xendit.co/sessions' => Http::response(['id' => 'sess_1'], 200)]);

        $service = new class(app(XenditClient::class)) {
            use HasApiVersion;

            // defaultApiVersion and apiVersionKey stay null (trait defaults)
            public function __construct(protected XenditClient $client) {}

            public function call(array $headers = []): void
            {
                $this->client->post('/sessions', [], $this->resolveHeaders($headers));
            }
        };

        $service->call();

        Http::assertSent(function ($request) {
            return ! $request->hasHeader('api-version');
        });
    }

    // Scenario 2: service declares $defaultApiVersion → that version is sent
    public function test_service_default_version_is_sent(): void
    {
        Http::fake(['api.xendit.co/sessions' => Http::response(['id' => 'sess_1'], 200)]);

        $service = new class(app(XenditClient::class)) {
            use HasApiVersion;

            // Assign in constructor — do NOT redeclare the property (PHP 8.4 forbids
            // redefining a trait property with a different initializer).
            public function __construct(protected XenditClient $client)
            {
                $this->defaultApiVersion = '2024-11-11';
            }

            public function call(array $headers = []): void
            {
                $this->client->post('/sessions', [], $this->resolveHeaders($headers));
            }
        };

        $service->call();

        Http::assertSent(function ($request) {
            return $request->hasHeader('api-version', '2024-11-11');
        });
    }

    // Scenario 5 (partial): builder withApiVersion() sets the header in $this->headers
    public function test_builder_with_api_version_stores_header(): void
    {
        $builder = new class extends \Laraditz\Xendit\Builders\BaseBuilder {
            public function create() { return $this; }
            public function getHeaders(): array { return $this->headers; }
        };

        $builder->withApiVersion('2025-01-01');

        $this->assertEquals('2025-01-01', $builder->getHeaders()['api-version']);
    }

    // Scenario 6 (partial): builder withoutApiVersion() stores null as the suppress marker
    public function test_builder_without_api_version_stores_null(): void
    {
        $builder = new class extends \Laraditz\Xendit\Builders\BaseBuilder {
            public function create() { return $this; }
            public function getHeaders(): array { return $this->headers; }
        };

        $builder->withoutApiVersion();

        $this->assertArrayHasKey('api-version', $builder->getHeaders());
        $this->assertNull($builder->getHeaders()['api-version']);
    }

    // Scenario 3: config enables version for a service that has null default
    public function test_config_version_enables_header_when_service_default_is_null(): void
    {
        Http::fake(['api.xendit.co/sessions' => Http::response(['id' => 'sess_1'], 200)]);

        $this->app['config']->set('xendit.api_versions', ['session' => '2024-05-01']);

        $service = app(\Laraditz\Xendit\Services\SessionService::class);
        // SessionService has $defaultApiVersion = null; config supplies the version
        $service->create(['session_type' => 'PAYMENT', 'currency' => 'MYR', 'amount' => 100]);

        Http::assertSent(function ($request) {
            return $request->hasHeader('api-version', '2024-05-01');
        });
    }

    // Scenario 4: config null suppresses a service's $defaultApiVersion (integration level)
    public function test_config_null_suppresses_service_default_version(): void
    {
        Http::fake(['api.xendit.co/sessions' => Http::response(['id' => 'sess_1'], 200)]);

        // Set the key to null using the full array form so array_key_exists() returns true.
        // Do NOT use dot notation: $this->app['config']->set('xendit.api_versions.session', null)
        // because that may not insert a null-valued key into the array reliably.
        $this->app['config']->set('xendit.api_versions', ['session' => null]);

        $service = new class(app(\Laraditz\Xendit\Client\XenditClient::class)) extends \Laraditz\Xendit\Services\SessionService {
            public function __construct($client)
            {
                parent::__construct($client);
                $this->defaultApiVersion = '2024-11-11';
            }
        };
        $service->create(['session_type' => 'PAYMENT', 'currency' => 'MYR', 'amount' => 100]);

        Http::assertSent(function ($request) {
            return ! $request->hasHeader('api-version');
        });
    }

    // Scenario 5 (full integration): builder withApiVersion() overrides config and service default
    public function test_builder_with_api_version_overrides_config_and_default(): void
    {
        Http::fake(['api.xendit.co/sessions/*' => Http::response(['id' => 'sess_1'], 200)]);

        $this->app['config']->set('xendit.api_versions', ['session' => '2024-05-01']);

        // Use get() so no DB record is required; get() passes $this->headers through to the service
        \Laraditz\Xendit\Facades\Xendit::session()
            ->withApiVersion('2025-01-01')
            ->get('sess_123');

        Http::assertSent(function ($request) {
            return $request->hasHeader('api-version', '2025-01-01');
        });
    }

    // Scenario 6 (full integration): builder withoutApiVersion() suppresses config and service default
    public function test_builder_without_api_version_suppresses_config_and_default(): void
    {
        Http::fake(['api.xendit.co/sessions/*' => Http::response(['id' => 'sess_1'], 200)]);

        $this->app['config']->set('xendit.api_versions', ['session' => '2024-05-01']);

        \Laraditz\Xendit\Facades\Xendit::session()
            ->withoutApiVersion()
            ->get('sess_123');

        Http::assertSent(function ($request) {
            return ! $request->hasHeader('api-version');
        });
    }
}
