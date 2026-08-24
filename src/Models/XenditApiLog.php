<?php

namespace Laraditz\Xendit\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class XenditApiLog extends Model
{
    use Prunable;

    protected $table = 'xendit_api_logs';

    protected $fillable = [
        'method',
        'endpoint',
        'reference_id',
        'request_payload',
        'response_payload',
        'http_status',
        'duration_ms',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(config('xendit.api_log_retention_days', 30)));
    }
}
