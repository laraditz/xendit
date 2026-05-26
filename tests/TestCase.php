<?php

namespace Laraditz\Xendit\Tests;

use Laraditz\Xendit\XenditServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    public static $latestResponse;

    protected function getPackageProviders($app): array
    {
        return [XenditServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('xendit.api_key', 'test_secret_key');
        $app['config']->set('xendit.base_url', 'https://api.xendit.co');
        $app['config']->set('xendit.default_currency', 'MYR');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
