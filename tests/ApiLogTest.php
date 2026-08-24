<?php

namespace Laraditz\Xendit\Tests;

use Illuminate\Support\Facades\Schema;

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
}
