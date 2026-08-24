<?php

namespace Laraditz\Xendit\Tests;

class ConfigApiLogKeysTest extends TestCase
{
    public function test_log_api_calls_defaults_to_true(): void
    {
        $raw = require __DIR__ . '/../config/config.php';

        $this->assertTrue($raw['log_api_calls']);
    }

    public function test_api_log_retention_days_defaults_to_30(): void
    {
        $raw = require __DIR__ . '/../config/config.php';

        $this->assertSame(30, $raw['api_log_retention_days']);
    }
}
