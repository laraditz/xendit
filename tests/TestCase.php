<?php

namespace Laraditz\Xendit\Tests;

use Laraditz\Xendit\XenditServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TestCase extends BaseTestCase
{
    use RefreshDatabase;

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
        $app['config']->set('xendit.api_version', '2024-11-11');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
