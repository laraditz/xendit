<?php

namespace Laraditz\Xendit\Models;

use Illuminate\Database\Eloquent\Model;

class XenditApiLog extends Model
{
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
}
