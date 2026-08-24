<?php

namespace Laraditz\Xendit\Tests;

use Illuminate\Support\Facades\Schema;
use Laraditz\Xendit\Models\XenditApiLog;

class ApiLogTest extends TestCase
{
    public function test_xendit_api_logs_table_has_expected_columns(): void
    {
        $columns = Schema::getColumnListing('xendit_api_logs');

        foreach ([
            'id', 'method', 'endpoint', 'reference_id',
            'request_payload', 'response_payload',
            'http_status', 'duration_ms',
            'created_at', 'updated_at',
        ] as $column) {
            $this->assertContains($column, $columns, "Missing column: $column");
        }
    }

    public function test_model_fillable_and_casts(): void
    {
        $log = XenditApiLog::create([
            'method' => 'POST',
            'endpoint' => '/refunds',
            'reference_id' => 'rfd-1',
            'request_payload' => ['amount' => 500],
            'response_payload' => ['id' => 'rfd-1', 'amount' => 500],
            'http_status' => 200,
            'duration_ms' => 123,
        ]);

        $log->refresh();

        $this->assertSame('POST', $log->method);
        $this->assertSame('/refunds', $log->endpoint);
        $this->assertSame('rfd-1', $log->reference_id);
        $this->assertIsArray($log->request_payload);
        $this->assertSame(['amount' => 500], $log->request_payload);
        $this->assertIsArray($log->response_payload);
        $this->assertSame(['id' => 'rfd-1', 'amount' => 500], $log->response_payload);
        $this->assertSame(200, $log->http_status);
        $this->assertSame(123, $log->duration_ms);
    }
}
