<?php

namespace Laraditz\Xendit\Tests;

use Illuminate\Support\Carbon;
use Laraditz\Xendit\Models\XenditApiLog;

class ApiLogPrunableTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_prunable_includes_rows_older_than_retention_window(): void
    {
        config(['xendit.api_log_retention_days' => 30]);

        Carbon::setTestNow(now()->subDays(31));
        $oldLog = XenditApiLog::create([
            'method' => 'GET',
            'endpoint' => '/payments',
            'http_status' => 200,
        ]);
        Carbon::setTestNow();

        $this->assertTrue((new XenditApiLog())->prunable()->pluck('id')->contains($oldLog->id));
    }

    public function test_prunable_excludes_rows_within_retention_window(): void
    {
        config(['xendit.api_log_retention_days' => 30]);

        $recentLog = XenditApiLog::create([
            'method' => 'GET',
            'endpoint' => '/payments',
            'http_status' => 200,
        ]);

        $this->assertFalse((new XenditApiLog())->prunable()->pluck('id')->contains($recentLog->id));
    }
}
